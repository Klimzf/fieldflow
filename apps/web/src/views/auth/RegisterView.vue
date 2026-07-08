<script setup lang="ts">
import { AxiosError } from 'axios'
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const error = ref<string | null>(null)

async function submit(): Promise<void> {
  error.value = null

  try {
    await auth.register(form)
    await router.push({ name: 'dashboard' })
  } catch (exception) {
    if (exception instanceof AxiosError && exception.response?.status === 422) {
      error.value = 'Проверьте введённые данные.'
      return
    }

    error.value = 'Не удалось создать аккаунт. Попробуйте позже.'
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="eyebrow">FieldFlow</p>

      <h1>Регистрация</h1>

      <p class="description">Создайте аккаунт для работы с FieldFlow.</p>

      <form class="form" @submit.prevent="submit">
        <label>
          Имя
          <input v-model="form.name" type="text" autocomplete="name" required />
        </label>

        <label>
          Email
          <input v-model="form.email" type="email" autocomplete="email" required />
        </label>

        <label>
          Пароль
          <input v-model="form.password" type="password" autocomplete="new-password" required />
        </label>

        <label>
          Повторите пароль
          <input
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          />
        </label>

        <p v-if="error" class="error">{{ error }}</p>

        <button type="submit" :disabled="auth.loading">
          {{ auth.loading ? 'Создание...' : 'Создать аккаунт' }}
        </button>
      </form>

      <p class="switch">
        Уже есть аккаунт?
        <RouterLink to="/login">Войти</RouterLink>
      </p>
    </section>
  </main>
</template>
