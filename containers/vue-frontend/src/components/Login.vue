<template>
    <div class="login-terminal">
      <div class="login-frame">
        <header class="login-header">
          <div class="login-title">[ RAGu LOGIN ]</div>
          <div class="login-subtitle">ENTER CREDENTIALS TO ACCESS</div>
        </header>
  
        <form class="login-form" @submit.prevent="onSubmit">
          <div class="field">
            <label for="username" class="field-label">username</label>
            <input
              id="username"
              v-model="username"
              type="text"
              autocomplete="username"
              class="field-input"
            />
          </div>
  
          <div class="field">
            <label for="password" class="field-label">password</label>
            <input
              id="password"
              v-model="password"
              type="password"
              autocomplete="current-password"
              class="field-input"
            />
          </div>
  
          <div class="actions">
            <button
              type="submit"
              class="btn-primary"
              :disabled="submitting || !username || !password"
            >
              <span v-if="!submitting">➤ log in</span>
              <span v-else>… verifying …</span>
            </button>
          </div>
  
          <p v-if="error" class="error-line">{{ error }}</p>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watch } from 'vue'
  
  const props = defineProps({
    errorMessage: {
      type: String,
      default: ''
    }
  })
  
  const emit = defineEmits(['submit'])
  
  const username = ref('')
  const password = ref('')
  const submitting = ref(false)
  const error = ref(props.errorMessage)
  
  watch(
    () => props.errorMessage,
    (val) => {
      error.value = val
      if (val) submitting.value = false
    }
  )
  
  function onSubmit() {
    if (!username.value || !password.value) {
      error.value = 'missing username or password'
      return
    }
    error.value = ''
    submitting.value = true
    emit('submit', { username: username.value, password: password.value })
  }
  </script>
  
  
  <style scoped>
  /* Outer background */
  .login-terminal {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  
    background: radial-gradient(circle at top, #121827 0%, #070a12 60%, #000000 100%);
    color: #daf2ff;
    font-family: "JetBrains Mono", monospace;
  }
  
  /* Frame (blue/cyan theme) */
  .login-frame {
    width: 100%;
    max-width: 420px;
    padding: 1.75rem 2rem;
    border-radius: 10px;
  
    background: linear-gradient(135deg, rgba(0, 4, 14, 0.95), rgba(10, 22, 40, 0.98));
    border: 1px solid rgba(0, 140, 255, 0.5);
  
    box-shadow:
      0 0 20px rgba(0, 110, 255, 0.28),
      0 0 4px rgba(0, 0, 0, 0.9) inset;
  
    position: relative;
    overflow: hidden;
  }
  
  /* CRT-ish scanlines */
  .login-frame::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
  
    background-image: linear-gradient(
      to bottom,
      rgba(255, 255, 255, 0.05) 1px,
      rgba(0, 0, 0, 0) 1px
    );
    background-size: 100% 2px;
    mix-blend-mode: soft-light;
    opacity: 0.14;
  }
  
  /* Corner blue glow */
  .login-frame::after {
    content: "";
    position: absolute;
    inset: -40%;
    background: radial-gradient(circle at top left, rgba(0, 150, 255, 0.30), transparent 55%);
    opacity: 0.6;
    pointer-events: none;
  }
  
  /* Header */
  .login-header {
    margin-bottom: 1.6rem;
  }
  
  .login-title {
    font-size: 1.15rem;
    letter-spacing: 0.1em;
  
    color: #7bd4ff;
    text-shadow:
      0 0 6px rgba(0, 170, 255, 0.9),
      0 0 18px rgba(0, 220, 255, 0.7);
  }
  
  .login-subtitle {
    margin-top: 0.35rem;
    font-size: 0.78rem;
    letter-spacing: 0.13em;
  
    color: rgba(160, 215, 255, 0.7);
  }
  
  /* Form */
  .field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 1rem;
  }
  
  .field-label {
    font-size: 0.78rem;
    color: rgba(155, 205, 255, 0.78);
    text-transform: lowercase;
  }
  
  .field-input {
    padding: 0.5rem 0.6rem;
    border-radius: 4px;
    border: 1px solid rgba(0, 160, 255, 0.45);
  
    background-color: rgba(0, 8, 18, 0.9);
    color: #e7f8ff;
    font-size: 0.86rem;
  
    outline: none;
    transition:
      border-color 0.15s ease-out,
      box-shadow 0.15s ease-out,
      background-color 0.15s ease-out,
      transform 0.08s ease-out;
  }
  
  .field-input:focus {
    border-color: rgba(0, 200, 255, 1);
    box-shadow:
      0 0 0 1px rgba(0, 200, 255, 0.6),
      0 0 14px rgba(0, 200, 255, 0.45);
    background-color: rgba(0, 12, 25, 0.98);
    transform: translateY(-1px);
  }
  
  /* Submit button */
  .actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.5rem;
  }
  
  .btn-primary {
    padding: 0.5rem 1.4rem;
    border-radius: 999px;
    border: 1px solid rgba(0, 170, 255, 0.7);
  
    background: radial-gradient(circle at top left, rgba(0, 170, 255, 0.18), rgba(0, 0, 0, 0.95));
    color: #dff7ff;
  
    font-size: 0.82rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  
    cursor: pointer;
    overflow: hidden;
  
    text-shadow: 0 0 5px rgba(0, 200, 255, 0.8);
  
    box-shadow:
      0 0 0 1px rgba(0, 170, 255, 0.4),
      0 0 18px rgba(0, 170, 255, 0.45);
  
    transition:
      transform 0.08s ease-out,
      border-color 0.12s ease-out,
      box-shadow 0.12s ease-out,
      opacity 0.15s ease-out;
  }
  
  .btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(0, 200, 255, 1);
  
    box-shadow:
      0 0 0 1px rgba(0, 200, 255, 0.6),
      0 0 22px rgba(0, 200, 255, 0.7);
  
    background: radial-gradient(circle at top left, rgba(0, 200, 255, 0.28), rgba(0, 0, 0, 0.95));
  }
  
  .btn-primary:active:not(:disabled) {
    transform: translateY(0);
    box-shadow:
      0 0 0 1px rgba(0, 140, 255, 0.7),
      0 0 12px rgba(0, 140, 255, 0.6);
  }
  
  .btn-primary:disabled {
    opacity: 0.55;
    cursor: default;
  
    box-shadow:
      0 0 0 1px rgba(0, 110, 170, 0.5),
      0 0 6px rgba(0, 110, 170, 0.4);
  }
  
  /* Error text */
  .error-line {
    margin-top: 0.9rem;
    font-size: 0.78rem;
  
    color: #ff7a9b;
    text-shadow: 0 0 6px rgba(255, 70, 120, 0.8);
  }
  </style>
  