<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <main class="dashboard">
    <header class="dashboard-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Панель управления</h1>
      </div>

      <button type="button" @click="logout">Выйти</button>
    </header>

    <section class="dashboard-card">
      <h2>Добро пожаловать, {{ auth.user?.name }}</h2>
      <p>Email: {{ auth.user?.email }}</p>
      <p>
        Это первая защищённая страница FieldFlow. Позже здесь появятся заявки, клиенты, инженеры и
        аналитика.
      </p>
    </section>
  </main>
</template>
