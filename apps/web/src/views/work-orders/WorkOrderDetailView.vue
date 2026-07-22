<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { WORK_ORDER_STATUS_LABELS, WORK_ORDER_STATUSES } from '@/shared/constants/work-orders'
import { useWorkOrderAssignmentsStore } from '@/stores/work-order-assignments'
import { useWorkOrderUpdatesStore } from '@/stores/work-order-updates'
import { useWorkOrdersStore } from '@/stores/work-orders'
import type { WorkOrderStatus } from '@/shared/types/work-order'
import type { WorkOrderUpdate } from '@/shared/types/work-order-update'

const route = useRoute()
const workOrdersStore = useWorkOrdersStore()
const updatesStore = useWorkOrderUpdatesStore()
const assignmentsStore = useWorkOrderAssignmentsStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))
const workOrderId = computed(() => Number(route.params.workOrderId))

const selectedStatus = ref<WorkOrderStatus>('new')
const selectedAssignableUserId = ref('')
const comment = ref('')
const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

const workOrder = computed(() => workOrdersStore.currentWorkOrder)

onMounted(async () => {
  await Promise.all([
    workOrdersStore.fetchWorkOrder(workOrderId.value),
    updatesStore.fetchUpdates(workOrderId.value),
    assignmentsStore.fetchAssignments(workOrderId.value),
    assignmentsStore.fetchAssignableUsers(workOrderId.value),
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
    handleError(exception, 'Не удалось изменить статус заявки. Попробуйте позже.')
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
    handleError(exception, 'Не удалось добавить комментарий. Попробуйте позже.')
  }
}

async function assignUser(): Promise<void> {
  if (selectedAssignableUserId.value === '') {
    return
  }

  error.value = null
  validationErrors.value = []

  try {
    await assignmentsStore.createAssignment(workOrderId.value, {
      user_id: Number(selectedAssignableUserId.value),
    })

    selectedAssignableUserId.value = ''
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось назначить пользователя. Попробуйте позже.')
  }
}

async function removeAssignment(assignmentId: number): Promise<void> {
  error.value = null
  validationErrors.value = []

  try {
    await assignmentsStore.deleteAssignment(assignmentId)
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось снять назначение. Попробуйте позже.')
  }
}

function handleError(exception: unknown, fallbackMessage: string): void {
  const validationError = getValidationError(exception)

  if (validationError !== null) {
    error.value = validationError.message
    validationErrors.value = validationError.errors

    return
  }

  error.value = fallbackMessage
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
      <h2>Назначения</h2>

      <p v-if="assignmentsStore.loading">Загрузка назначений...</p>

      <div v-else-if="assignmentsStore.assignments.length === 0" class="empty-state">
        <p>Пока никто не назначен на заявку.</p>
      </div>

      <div v-else class="organization-list">
        <article
          v-for="assignment in assignmentsStore.assignments"
          :key="assignment.id"
          class="organization-item"
        >
          <div>
            <h3>{{ assignment.user?.name ?? 'Пользователь' }}</h3>
            <p v-if="assignment.user?.email">{{ assignment.user.email }}</p>
            <p v-if="assignment.assigned_by">Назначил: {{ assignment.assigned_by.name }}</p>
            <p v-if="assignment.created_at">Дата назначения: {{ assignment.created_at }}</p>
          </div>

          <button
            type="button"
            :disabled="assignmentsStore.loading"
            @click="removeAssignment(assignment.id)"
          >
            Снять
          </button>
        </article>
      </div>

      <form class="form compact-form" @submit.prevent="assignUser">
        <label>
          Назначить пользователя
          <select v-model="selectedAssignableUserId" required>
            <option value="">Выберите пользователя</option>

            <option
              v-for="user in assignmentsStore.availableAssignableUsers"
              :key="user.id"
              :value="String(user.id)"
            >
              {{ user.name }} — {{ user.email }} — {{ user.role }}
            </option>
          </select>
        </label>

        <button
          type="submit"
          :disabled="assignmentsStore.loading || selectedAssignableUserId === ''"
        >
          Назначить
        </button>
      </form>
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
