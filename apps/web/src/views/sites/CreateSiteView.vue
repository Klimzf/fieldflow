<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { useSitesStore } from '@/stores/sites'

const route = useRoute()
const router = useRouter()
const sitesStore = useSitesStore()

const clientId = computed(() => Number(route.params.clientId))

const form = reactive({
  name: '',
  address: '',
  contact_name: '',
  contact_phone: '',
  notes: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

async function submit(): Promise<void> {
  error.value = null
  validationErrors.value = []

  try {
    await sitesStore.createSite(clientId.value, {
      name: form.name,
      address: form.address || undefined,
      contact_name: form.contact_name || undefined,
      contact_phone: form.contact_phone || undefined,
      notes: form.notes || undefined,
    })

    await router.push({
      name: 'client.sites',
      params: {
        clientId: clientId.value,
      },
    })
  } catch (exception: unknown) {
    const validationError = getValidationError(exception)

    if (validationError !== null) {
      error.value = validationError.message
      validationErrors.value = validationError.errors

      return
    }

    error.value = 'Не удалось создать объект. Попробуйте позже.'
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="eyebrow">FieldFlow</p>

      <h1>Новый объект</h1>

      <p class="description">Объект принадлежит выбранному клиенту.</p>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input v-model="form.name" type="text" required placeholder="Например: Главный офис" />
        </label>

        <label>
          Адрес
          <input v-model="form.address" type="text" placeholder="Адрес объекта" />
        </label>

        <label>
          Контактное лицо
          <input v-model="form.contact_name" type="text" placeholder="Иван Иванов" />
        </label>

        <label>
          Контактный телефон
          <input v-model="form.contact_phone" type="text" placeholder="+79990000000" />
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

        <button type="submit" :disabled="sitesStore.loading">
          {{ sitesStore.loading ? 'Создание...' : 'Создать объект' }}
        </button>
      </form>

      <p class="switch">
        <RouterLink :to="{ name: 'client.sites', params: { clientId } }">
          Назад к объектам
        </RouterLink>
      </p>
    </section>
  </main>
</template>
