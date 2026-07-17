<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { useEquipmentStore } from '@/stores/equipment'
import { useWorkOrdersStore } from '@/stores/work-orders'
import type { WorkOrderPriority, WorkOrderStatus } from '@/shared/types/work-order'

const route = useRoute()
const router = useRouter()
const workOrdersStore = useWorkOrdersStore()
const equipmentStore = useEquipmentStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))

const statuses: Array<{ value: WorkOrderStatus; label: string }> = [
  { value: 'new', label: 'Новая' },
  { value: 'in_progress', label: 'В работе' },
  { value: 'completed', label: 'Выполнена' },
  { value: 'cancelled', label: 'Отменена' },
]

const priorities: Array<{ value: WorkOrderPriority; label: string }> = [
  { value: 'low', label: 'Низкий' },
  { value: 'medium', label: 'Средний' },
  { value: 'high', label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' },
]

const form = reactive({
  title: '',
  description: '',
  status: 'new' as WorkOrderStatus,
  priority: 'medium' as WorkOrderPriority,
  equipment_id: '',
  scheduled_at: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

onMounted(async () => {
  await equipmentStore.fetchEquipment(siteId.value)
})

async function submit(): Promise<void> {
  error.value = null
  validationErrors.value = []

  try {
    await workOrdersStore.createWorkOrder(siteId.value, {
      title: form.title,
      description: form.description || undefined,
      status: form.status,
      priority: form.priority,
      equipment_id: form.equipment_id === '' ? undefined : Number(form.equipment_id),
      scheduled_at: form.scheduled_at || undefined,
    })

    await router.push({
      name: 'site.work-orders',
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

    error.value = 'Не удалось создать заявку. Попробуйте позже.'
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-card">
      <p class="eyebrow">FieldFlow</p>

      <h1>Новая заявка</h1>

      <p class="description">Заявка принадлежит выбранному объекту.</p>

      <form class="form" @submit.prevent="submit">
        <label>
          Заголовок
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="Например: Не работает насос"
          />
        </label>

        <label>
          Оборудование
          <select v-model="form.equipment_id">
            <option value="">Без конкретного оборудования</option>

            <option
              v-for="equipment in equipmentStore.equipmentItems"
              :key="equipment.id"
              :value="String(equipment.id)"
            >
              {{ equipment.name }}
            </option>
          </select>
        </label>

        <label>
          Статус
          <select v-model="form.status">
            <option v-for="status in statuses" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </label>

        <label>
          Приоритет
          <select v-model="form.priority">
            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">
              {{ priority.label }}
            </option>
          </select>
        </label>

        <label>
          Запланировать
          <input v-model="form.scheduled_at" type="datetime-local" />
        </label>

        <label>
          Описание
          <textarea v-model="form.description" rows="4" placeholder="Опишите проблему или задачу" />
        </label>

        <div v-if="error" class="error">
          <p>{{ error }}</p>

          <ul v-if="validationErrors.length">
            <li v-for="validationError in validationErrors" :key="validationError">
              {{ validationError }}
            </li>
          </ul>
        </div>

        <button type="submit" :disabled="workOrdersStore.loading">
          {{ workOrdersStore.loading ? 'Создание...' : 'Создать заявку' }}
        </button>
      </form>

      <p class="switch">
        <RouterLink
          :to="{
            name: 'site.work-orders',
            params: {
              clientId,
              siteId,
            },
          }"
        >
          Назад к заявкам
        </RouterLink>
      </p>
    </section>
  </main>
</template>
