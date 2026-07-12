import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { csrf, http } from '@/shared/api/http'
import type { User } from '@/shared/types/user'
import type { ApiResource } from '@/shared/types/api'

interface LoginPayload {
  email: string
  password: string
  remember: boolean
}

interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const initialized = ref(false)
  const loading = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  async function fetchUser(): Promise<void> {
    try {
      const response = await http.get<ApiResource<User>>('/api/auth/user')
      user.value = response.data.data
    } catch {
      user.value = null
    } finally {
      initialized.value = true
    }
  }

  async function login(payload: LoginPayload): Promise<void> {
    loading.value = true

    try {
      await csrf()

      const response = await http.post<ApiResource<User>>('/api/auth/login', payload)

      user.value = response.data.data
      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  async function register(payload: RegisterPayload): Promise<void> {
    loading.value = true

    try {
      await csrf()

      const response = await http.post<ApiResource<User>>('/api/auth/register', payload)

      user.value = response.data.data
      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    loading.value = true

    try {
      await http.post('/api/auth/logout')
      user.value = null
      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    initialized,
    loading,
    isAuthenticated,
    fetchUser,
    login,
    register,
    logout,
  }
})
