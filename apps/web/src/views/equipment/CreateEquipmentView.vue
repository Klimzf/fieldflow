<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { useEquipmentStore } from '@/stores/equipment'

const route = useRoute()
const router = useRouter()
const equipmentStore = useEquipmentStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))

const form = reactive({
  name: '',
  type: '',
  manufacturer: '',
  model: '',
  serial_number: '',
  installed_at: '',
  notes: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

async function submit(): Promise<void> {
  error.value = null
  validationErrors.value = []

  try {
    await equipmentStore.createEquipment(siteId.value, {
      name: form.name,
      type: form.type || undefined,
      manufacturer: form.manufacturer || undefined,
      model: form.model || undefined,
      serial_number: form.serial_number || undefined,
      installed_at: form.installed_at || undefined,
      notes: form.notes || undefined,
    })

    await router.push({
      name: 'site.equipment',
      params: {
        clientId: clientId.value,
        siteId: siteId.value,
      },
    })
  } catch (exception: unknown) {
    const validationError = getValidationError(exception)

    if (validationError !== null) {
      error.value = validationError.message
      validationErrors.value = validationError.errors

      return
    }

    error.value = 'Не удалось добавить оборудование. Попробуйте позже.'
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="eyebrow">FieldFlow</p>

      <h1>Новое оборудование</h1>

      <p class="description">Оборудование принадлежит выбранному объекту.</p>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input v-model="form.name" type="text" required placeholder="Например: Насос №1" />
        </label>

        <label>
          Тип
          <input v-model="form.type" type="text" placeholder="pump, boiler, conditioner..." />
        </label>

        <label>
          Производитель
          <input v-model="form.manufacturer" type="text" placeholder="Например: Grundfos" />
        </label>

        <label>
          Модель
          <input v-model="form.model" type="text" placeholder="Например: P-100" />
        </label>

        <label>
          Серийный номер
          <input v-model="form.serial_number" type="text" placeholder="Например: SN-001" />
        </label>

        <label>
          Дата установки
          <input v-model="form.installed_at" type="date" />
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

        <button type="submit" :disabled="equipmentStore.loading">
          {{ equipmentStore.loading ? 'Создание...' : 'Добавить оборудование' }}
        </button>
      </form>

      <p class="switch">
        <RouterLink
          :to="{
            name: 'site.equipment',
            params: {
              clientId,
              siteId,
            },
          }"
        >
          Назад к оборудованию
        </RouterLink>
      </p>
    </section>
  </main>
</template>
