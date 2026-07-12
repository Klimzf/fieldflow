<script setup lang="ts">
import axios from 'axios'
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useClientsStore } from '@/stores/clients'

interface ValidationErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}

const route = useRoute()
const router = useRouter()
const clientsStore = useClientsStore()

const organizationId = computed(() => Number(route.params.organizationId))

const form = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  notes: '',
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
    await clientsStore.createClient(organizationId.value, {
      name: form.name,
      email: form.email || undefined,
      phone: form.phone || undefined,
      address: form.address || undefined,
      notes: form.notes || undefined,
    })

    await router.push({
      name: 'organization.clients',
      params: {
        organizationId: organizationId.value,
      },
    })
  } catch (exception: unknown) {
    if (!axios.isAxiosError<ValidationErrorResponse>(exception)) {
      error.value = 'Не удалось создать клиента. Попробуйте позже.'
      return
    }

    if (exception.response?.status === 422) {
      const data = exception.response.data

      error.value = data.message || 'Проверьте введённые данные.'
      validationErrors.value = getValidationErrors(data.errors)

      return
    }

    error.value = 'Не удалось создать клиента. Попробуйте позже.'
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="eyebrow">FieldFlow</p>

      <h1>Новый клиент</h1>

      <p class="description">Клиент принадлежит выбранной организации.</p>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input v-model="form.name" type="text" required placeholder="Например: Acme Client" />
        </label>

        <label>
          Email
          <input v-model="form.email" type="email" placeholder="client@example.com" />
        </label>

        <label>
          Телефон
          <input v-model="form.phone" type="text" placeholder="+79990000000" />
        </label>

        <label>
          Адрес
          <input v-model="form.address" type="text" placeholder="Адрес клиента" />
        </label>

        <label>
          Заметки
          <textarea v-model="form.notes" rows="4" placeholder="Дополнительная информация" />
        </label>

        <div v-if="error" class="error">
          <p>{{ error }}</p>

          <ul v-if="validationErrors.length">
            <li v-for="validationError in validationErrors" :key="validationError">
              {{ validationError }}
            </li>
          </ul>
        </div>

        <button type="submit" :disabled="clientsStore.loading">
          {{ clientsStore.loading ? 'Создание...' : 'Создать клиента' }}
        </button>
      </form>

      <p class="switch">
        <RouterLink :to="{ name: 'organization.clients', params: { organizationId } }">
          Назад к клиентам
        </RouterLink>
      </p>
    </section>
  </main>
</template>
