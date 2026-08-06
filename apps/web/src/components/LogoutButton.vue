<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function logout(): Promise<void> {
  await authStore.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <button type="button" @click="logout">Выйти</button>
</template>
