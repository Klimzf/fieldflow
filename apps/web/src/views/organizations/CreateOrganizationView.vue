<script setup lang="ts">
import axios from 'axios'
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useOrganizationsStore } from '@/stores/organizations'

interface ValidationErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

const router = useRouter()
const organizationsStore = useOrganizationsStore()

const form = reactive({
  name: '',
  slug: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

function getValidationErrors(errors: Record<string, string[]> | undefined): string[] {
  return Object.values(errors ?? {}).flat()
}

async function submit(): Promise<void> {
  error.value = null
  validationErrors.value = []

  try {
    await organizationsStore.createOrganization({
      name: form.name,
      slug: form.slug || undefined,
    })

    await router.push('/organizations')
  } catch (exception: unknown) {
    if (!axios.isAxiosError<ValidationErrorResponse>(exception)) {
      error.value = 'Не удалось создать организацию. Попробуйте позже.'
      return
    }

    if (exception.response?.status === 422) {
      const data = exception.response.data

      error.value = data.message || 'Проверьте введённые данные.'
      validationErrors.value = getValidationErrors(data.errors)

      return
    }

    error.value = 'Не удалось создать организацию. Попробуйте позже.'
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="eyebrow">FieldFlow</p>

      <h1>Новая организация</h1>

      <p class="description">
        Организация объединяет сотрудников, клиентов, объекты, оборудование и заявки.
      </p>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input v-model="form.name" type="text" required placeholder="Например: Acme Service" />
        </label>

        <label>
          Slug
          <input v-model="form.slug" type="text" placeholder="Можно оставить пустым" />
        </label>

        <div v-if="error" class="error">
          <p>{{ error }}</p>

          <ul v-if="validationErrors.length">
            <li v-for="validationError in validationErrors" :key="validationError">
              {{ validationError }}
            </li>
          </ul>
        </div>

        <button type="submit" :disabled="organizationsStore.loading">
          {{ organizationsStore.loading ? 'Создание...' : 'Создать' }}
        </button>
      </form>

      <p class="switch">
        <RouterLink to="/organizations">Назад к организациям</RouterLink>
      </p>
    </section>
  </main>
</template>
