<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { WORK_ORDER_STATUS_LABELS, WORK_ORDER_STATUSES } from '@/shared/constants/work-orders'
import { useWorkOrderUpdatesStore } from '@/stores/work-order-updates'
import { useWorkOrdersStore } from '@/stores/work-orders'
import type { WorkOrderStatus } from '@/shared/types/work-order'
import type { WorkOrderUpdate } from '@/shared/types/work-order-update'

const route = useRoute()
const workOrdersStore = useWorkOrdersStore()
const updatesStore = useWorkOrderUpdatesStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))
const workOrderId = computed(() => Number(route.params.workOrderId))

const selectedStatus = ref<WorkOrderStatus>('new')
const comment = ref('')
const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

const workOrder = computed(() => workOrdersStore.currentWorkOrder)

onMounted(async () => {
  await Promise.all([
    workOrdersStore.fetchWorkOrder(workOrderId.value),
    updatesStore.fetchUpdates(workOrderId.value),
  ])

  if (workOrdersStore.currentWorkOrder !== null) {
    selectedStatus.value = workOrdersStore.currentWorkOrder.status
  }
})

async function updateStatus(): Promise<void> {
  if (workOrder.value === null || selectedStatus.value === workOrder.value.status) {
    return
  }

  error.value = null
  validationErrors.value = []

  try {
    await workOrdersStore.updateWorkOrder(workOrderId.value, {
      status: selectedStatus.value,
    })

    await updatesStore.fetchUpdates(workOrderId.value)
  } catch (exception: unknown) {
    const validationError = getValidationError(exception)

    if (validationError !== null) {
      error.value = validationError.message
      validationErrors.value = validationError.errors

      return
    }

    error.value = 'Не удалось изменить статус заявки. Попробуйте позже.'
  }
}

async function submitComment(): Promise<void> {
  error.value = null
  validationErrors.value = []

  try {
    await updatesStore.createUpdate(workOrderId.value, {
      message: comment.value,
    })

    comment.value = ''
  } catch (exception: unknown) {
    const validationError = getValidationError(exception)

    if (validationError !== null) {
      error.value = validationError.message
      validationErrors.value = validationError.errors

      return
    }

    error.value = 'Не удалось добавить комментарий. Попробуйте позже.'
  }
}

function formatUpdate(update: WorkOrderUpdate): string {
  if (update.type === 'created') {
    return `Заявка создана со статусом "${formatStatus(update.new_status)}".`
  }

  if (update.type === 'status_changed') {
    return `Статус изменён с "${formatStatus(update.old_status)}" на "${formatStatus(
      update.new_status,
    )}".`
  }

  return update.message ?? ''
}

function formatStatus(status: string | null): string {
  if (status === null) {
    return 'не указан'
  }

  return WORK_ORDER_STATUS_LABELS[status as WorkOrderStatus] ?? status
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>{{ workOrder?.title ?? 'Заявка' }}</h1>
      </div>

      <RouterLink :to="{ name: 'site.work-orders', params: { clientId, siteId } }">
        Назад к заявкам
      </RouterLink>
    </header>

    <section class="card">
      <p v-if="workOrdersStore.loading">Загрузка заявки...</p>

      <template v-else-if="workOrder">
        <h2>Информация</h2>

        <p>Статус: {{ WORK_ORDER_STATUS_LABELS[workOrder.status] }}</p>
        <p v-if="workOrder.description">Описание: {{ workOrder.description }}</p>
        <p v-if="workOrder.equipment_id">Оборудование ID: {{ workOrder.equipment_id }}</p>
        <p v-if="workOrder.scheduled_at">Запланировано: {{ workOrder.scheduled_at }}</p>

        <div class="form compact-form">
          <label>
            Изменить статус
            <select v-model="selectedStatus">
              <option
                v-for="status in WORK_ORDER_STATUSES"
                :key="status.value"
                :value="status.value"
              >
                {{ status.label }}
              </option>
            </select>
          </label>

          <button
            type="button"
            :disabled="workOrdersStore.loading || selectedStatus === workOrder.status"
            @click="updateStatus"
          >
            Сохранить статус
          </button>
        </div>
      </template>
    </section>

    <section class="card">
      <h2>Добавить комментарий</h2>

      <form class="form" @submit.prevent="submitComment">
        <label>
          Комментарий
          <textarea
            v-model="comment"
            rows="4"
            required
            placeholder="Например: Проверил оборудование на объекте"
          />
        </label>

        <div v-if="error" class="error">
          <p>{{ error }}</p>

          <ul v-if="validationErrors.length">
            <li v-for="validationError in validationErrors" :key="validationError">
              {{ validationError }}
            </li>
          </ul>
        </div>

        <button type="submit" :disabled="updatesStore.loading">
          {{ updatesStore.loading ? 'Добавление...' : 'Добавить комментарий' }}
        </button>
      </form>
    </section>

    <section class="card">
      <h2>История заявки</h2>

      <p v-if="updatesStore.loading">Загрузка истории...</p>

      <div v-else-if="updatesStore.updates.length === 0" class="empty-state">
        <p>Истории пока нет.</p>
      </div>

      <div v-else class="timeline">
        <article v-for="update in updatesStore.updates" :key="update.id" class="timeline-item">
          <p>{{ formatUpdate(update) }}</p>

          <small>
            {{ update.user?.name ?? 'Система' }}
            <span v-if="update.created_at"> — {{ update.created_at }}</span>
          </small>
        </article>
      </div>
    </section>
  </main>
</template>
