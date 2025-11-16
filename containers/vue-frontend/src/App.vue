<template>
    <div class="app-root">
      <!-- Not authenticated: show login -->
      <Login
        v-if="!isAuthenticated"
        @submit="handleLogin"
        :error-message="loginError"
      />
  
      <!-- Authenticated: show chat -->
      <RetroTerminalChat
        v-else
        api-base="http://localhost:8080/v1"
        model="rag-local"
        system-prompt="You are a precise assistant grounded in provided context."
        profile-name="Luka K."
        profile-email="luka@example.com"
        profile-avatar=""
        :headers="authHeaders"
        @logout="handleLogout"
      />
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import Login from './components/Login.vue'
  import RetroTerminalChat from './components/RetroTerminalChat.vue'
  
  const accessToken = ref(null)
  const refreshToken = ref(null)
  const loginError = ref('')
  const isRefreshing = ref(false)
  
  const isAuthenticated = computed(() => !!accessToken.value)
  
  const authHeaders = computed(() =>
    accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : {}
  )
  
  const STORAGE_KEYS = {
    ACCESS: 'ragu_access_token',
    REFRESH: 'ragu_refresh_token',
  }
  
  function saveTokens(at, rt) {
    accessToken.value = at || null
    refreshToken.value = rt || null
  
    if (at) {
      localStorage.setItem(STORAGE_KEYS.ACCESS, at)
    } else {
      localStorage.removeItem(STORAGE_KEYS.ACCESS)
    }
  
    if (rt) {
      localStorage.setItem(STORAGE_KEYS.REFRESH, rt)
    } else {
      localStorage.removeItem(STORAGE_KEYS.REFRESH)
    }
  }
  
  function loadTokens() {
    const at = localStorage.getItem(STORAGE_KEYS.ACCESS)
    const rt = localStorage.getItem(STORAGE_KEYS.REFRESH)
    accessToken.value = at
    refreshToken.value = rt
  }
  
  function clearAuth() {
    saveTokens(null, null)
    // IMPORTANT: don't reset loginError here
  }
  
  /**
   * Minimal JWT decode just for "exp"
   */
  function decodeJwt(token) {
    try {
      const parts = token.split('.')
      if (parts.length !== 3) return null
      const payload = parts[1].replace(/-/g, '+').replace(/_/g, '/')
      const json = atob(payload)
      return JSON.parse(json)
    } catch {
      return null
    }
  }
  
  function isTokenValid(token, skewSeconds = 30) {
    if (!token) return false
    const decoded = decodeJwt(token)
    if (!decoded || !decoded.exp) return false
    const now = Math.floor(Date.now() / 1000)
    return decoded.exp > now + skewSeconds
  }
  
  async function tryRefresh() {
    if (!refreshToken.value) return false
    if (!isTokenValid(refreshToken.value, 0)) return false
  
    isRefreshing.value = true
    try {
      const res = await fetch('/api/auth/refresh', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refreshToken: refreshToken.value }),
      })
  
      if (!res.ok) {
        clearAuth()
        return false
      }
  
      const data = await res.json()
      if (!data.accessToken || !data.refreshToken) {
        clearAuth()
        return false
      }
  
      if (!isTokenValid(data.accessToken)) {
        clearAuth()
        return false
      }
  
      saveTokens(data.accessToken, data.refreshToken)
      loginError.value = '' // ensure no error when we’re successfully logged in
      return true
    } catch (e) {
      console.error('refresh failed', e)
      clearAuth()
      loginError.value = 'Unable to refresh session'
      return false
    } finally {
      isRefreshing.value = false
    }
  }
  
  async function handleLogin({ username, password }) {
    loginError.value = ''
  
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password }),
      })
  
      if (!res.ok) {
        clearAuth()
        loginError.value = 'Invalid username or password'
        return
      }
  
      const data = await res.json()
      if (!data.accessToken || !data.refreshToken) {
        clearAuth()
        loginError.value = 'Malformed login response'
        return
      }
  
      if (!isTokenValid(data.accessToken)) {
        clearAuth()
        loginError.value = 'Received expired token'
        return
      }
  
      saveTokens(data.accessToken, data.refreshToken)
      loginError.value = ''
    } catch (e) {
      console.error('login failed', e)
      clearAuth()
      loginError.value = 'Network or server error'
    }
  }
  
  function handleLogout() {
    clearAuth()
    loginError.value = ''
  }
  
  async function initAuthFromStorage() {
    loadTokens()
  
    if (isTokenValid(accessToken.value)) {
      loginError.value = ''
      return
    }
  
    accessToken.value = null
  
    if (refreshToken.value && isTokenValid(refreshToken.value, 0)) {
      await tryRefresh()
    } else {
      clearAuth()
    }
  }
  
  onMounted(() => {
    initAuthFromStorage()
  })
  </script>
  