<template>
    <RetroTerminalChat
    api-base="http://localhost:8080/v1"
    model="rag-local"
    system-prompt="You are a precise assistant grounded in provided context."
    profile-name="Luka K."
    profile-email="luka@example.com"
    profile-avatar=""
  />
  
  </template>
  
  <script setup>
  import RetroTerminalChat from './components/RetroTerminalChat.vue'
  </script>