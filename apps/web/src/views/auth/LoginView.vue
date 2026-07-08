<script setup lang="ts">
import { AxiosError } from 'axios'
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const error = ref<string | null>(null)

async function submit(): Promise<void> {
  error.value = null

  try {
    await auth.login(form)
    await router.push({ name: 'dashboard' })
  } catch (exception) {
    if (exception instanceof AxiosError && exception.response?.status === 422) {
      error.value = 'Неверный email или пароль.'
      return
    }

    error.value = 'Не удалось выполнить вход. Попробуйте позже.'
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="eyebrow">FieldFlow</p>

      <h1>Вход</h1>

      <p class="description">Войдите в систему управления выездным обслуживанием.</p>

      <form class="form" @submit.prevent="submit">
        <label>
          Email
          <input v-model="form.email" type="email" autocomplete="email" required />
        </label>

        <label>
          Пароль
          <input v-model="form.password" type="password" autocomplete="current-password" required />
        </label>

        <label class="checkbox">
          <input v-model="form.remember" type="checkbox" />
          Запомнить меня
        </label>

        <p v-if="error" class="error">{{ error }}</p>

        <button type="submit" :disabled="auth.loading">
          {{ auth.loading ? 'Вход...' : 'Войти' }}
        </button>
      </form>

      <p class="switch">
        Нет аккаунта?
        <RouterLink to="/register">Зарегистрироваться</RouterLink>
      </p>
    </section>
  </main>
</template>
