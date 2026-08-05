<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import { canManageRole } from '@/shared/constants/organization-roles'
import { WORK_ORDER_STATUS_LABELS, WORK_ORDER_STATUSES } from '@/shared/constants/work-orders'
import { useAuthStore } from '@/stores/auth'
import { useOrganizationsStore } from '@/stores/organizations'
import { useWorkOrderAssignmentsStore } from '@/stores/work-order-assignments'
import { useWorkOrderChecklistItemsStore } from '@/stores/work-order-checklist-items'
import { useWorkOrderFilesStore } from '@/stores/work-order-files'
import { useWorkOrderUpdatesStore } from '@/stores/work-order-updates'
import { useWorkOrdersStore } from '@/stores/work-orders'
import type { WorkOrderStatus } from '@/shared/types/work-order'
import type { WorkOrderChecklistItem } from '@/shared/types/work-order-checklist-item'
import type { WorkOrderFile } from '@/shared/types/work-order-file'
import type { WorkOrderUpdate } from '@/shared/types/work-order-update'

const route = useRoute()
const authStore = useAuthStore()
const organizationsStore = useOrganizationsStore()
const workOrdersStore = useWorkOrdersStore()
const updatesStore = useWorkOrderUpdatesStore()
const assignmentsStore = useWorkOrderAssignmentsStore()
const checklistStore = useWorkOrderChecklistItemsStore()
const filesStore = useWorkOrderFilesStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))
const workOrderId = computed(() => Number(route.params.workOrderId))

const selectedStatus = ref<WorkOrderStatus>('new')
const selectedAssignableUserId = ref('')
const comment = ref('')
const selectedFile = ref<File | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const checklistForm = reactive({
  title: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

const workOrder = computed(() => workOrdersStore.currentWorkOrder)

const currentUserId = computed(() => authStore.user?.id ?? null)

const canManageCurrentOrganization = computed(() => {
  if (workOrder.value === null) {
    return false
  }

  return canManageRole(organizationsStore.organizationRole(workOrder.value.organization_id))
})

onMounted(async () => {
  await Promise.all([
    organizationsStore.fetchOrganizations(),
    workOrdersStore.fetchWorkOrder(workOrderId.value),
    updatesStore.fetchUpdates(workOrderId.value),
    assignmentsStore.fetchAssignments(workOrderId.value),
    assignmentsStore.fetchAssignableUsers(workOrderId.value),
    checklistStore.fetchItems(workOrderId.value),
    filesStore.fetchFiles(workOrderId.value),
  ])

  if (workOrdersStore.currentWorkOrder !== null) {
    selectedStatus.value = workOrdersStore.currentWorkOrder.status
  }
})

async function updateStatus(): Promise<void> {
  if (workOrder.value === null || selectedStatus.value === workOrder.value.status) {
    return
  }

  clearErrors()

  try {
    await workOrdersStore.updateWorkOrder(workOrderId.value, {
      status: selectedStatus.value,
    })

    await updatesStore.fetchUpdates(workOrderId.value)
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось изменить статус заявки. Попробуйте позже.')
  }
}

async function submitChecklistItem(): Promise<void> {
  if (!canManageCurrentOrganization.value) {
    return
  }

  clearErrors()

  try {
    await checklistStore.createItem(workOrderId.value, {
      title: checklistForm.title,
    })

    checklistForm.title = ''
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось добавить пункт чек-листа. Попробуйте позже.')
  }
}

async function downloadServiceReport(): Promise<void> {
  clearErrors()

  try {
    await workOrdersStore.downloadServiceReport(workOrderId.value)
  } catch {
    error.value = 'Не удалось скачать PDF-акт. Попробуйте позже.'
  }
}

async function toggleChecklistItem(item: WorkOrderChecklistItem): Promise<void> {
  clearErrors()

  try {
    await checklistStore.updateCompletion(item.id, {
      is_completed: !item.is_completed,
    })
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось обновить пункт чек-листа. Попробуйте позже.')
  }
}

async function removeChecklistItem(itemId: number): Promise<void> {
  if (!canManageCurrentOrganization.value) {
    return
  }

  clearErrors()

  try {
    await checklistStore.deleteItem(itemId)
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось удалить пункт чек-листа. Попробуйте позже.')
  }
}

function handleFileChange(event: Event): void {
  const input = event.target as HTMLInputElement

  selectedFile.value = input.files?.item(0) ?? null
}

async function submitFile(): Promise<void> {
  if (selectedFile.value === null) {
    return
  }

  clearErrors()

  try {
    await filesStore.uploadFile(workOrderId.value, selectedFile.value)

    selectedFile.value = null

    if (fileInput.value !== null) {
      fileInput.value.value = ''
    }
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось загрузить файл. Попробуйте позже.')
  }
}

async function downloadFile(fileId: number): Promise<void> {
  const file = filesStore.files.find((item) => item.id === fileId)

  if (file === undefined) {
    return
  }

  clearErrors()

  try {
    await filesStore.downloadFile(file)
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось скачать файл. Попробуйте позже.')
  }
}

async function removeFile(file: WorkOrderFile): Promise<void> {
  if (!canDeleteFile(file)) {
    return
  }

  clearErrors()

  try {
    await filesStore.deleteFile(file.id)
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось удалить файл. Попробуйте позже.')
  }
}

async function submitComment(): Promise<void> {
  clearErrors()

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
  if (!canManageCurrentOrganization.value || selectedAssignableUserId.value === '') {
    return
  }

  clearErrors()

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
  if (!canManageCurrentOrganization.value) {
    return
  }

  clearErrors()

  try {
    await assignmentsStore.deleteAssignment(assignmentId)
  } catch (exception: unknown) {
    handleError(exception, 'Не удалось снять назначение. Попробуйте позже.')
  }
}

function clearErrors(): void {
  error.value = null
  validationErrors.value = []
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

function formatFileSize(size: number): string {
  if (size < 1024) {
    return `${size} B`
  }

  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`
  }

  return `${(size / 1024 / 1024).toFixed(1)} MB`
}

function canDeleteFile(file: WorkOrderFile): boolean {
  return file.uploaded_by_id === currentUserId.value || canManageCurrentOrganization.value
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

    <section v-if="error" class="card">
      <div class="error">
        <p>{{ error }}</p>

        <ul v-if="validationErrors.length">
          <li v-for="validationError in validationErrors" :key="validationError">
            {{ validationError }}
          </li>
        </ul>
      </div>
    </section>

    <section class="card">
      <p v-if="workOrdersStore.loading">Загрузка заявки...</p>

      <template v-else-if="workOrder">
        <h2>Информация</h2>

        <p>Статус: {{ WORK_ORDER_STATUS_LABELS[workOrder.status] }}</p>
        <p v-if="workOrder.description">Описание: {{ workOrder.description }}</p>
        <p v-if="workOrder.equipment_id">Оборудование ID: {{ workOrder.equipment_id }}</p>
        <p v-if="workOrder.scheduled_at">Запланировано: {{ workOrder.scheduled_at }}</p>

        <div class="organization-actions">
          <button type="button" :disabled="workOrdersStore.loading" @click="downloadServiceReport">
            Скачать акт PDF
          </button>
        </div>
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
      <h2>Чек-лист работ</h2>

      <p class="description">
        Выполнено {{ checklistStore.completedCount }} из {{ checklistStore.totalCount }}
      </p>

      <p v-if="checklistStore.loading">Загрузка чек-листа...</p>

      <div v-else-if="checklistStore.items.length === 0" class="empty-state">
        <p>Пункты чек-листа пока не добавлены.</p>
      </div>

      <div v-else class="checklist">
        <article
          v-for="item in checklistStore.items"
          :key="item.id"
          class="checklist-item"
          :class="{ completed: item.is_completed }"
        >
          <div>
            <h3>{{ item.title }}</h3>

            <p v-if="item.is_completed">
              Выполнил:
              {{ item.completed_by?.name ?? 'Пользователь' }}
              <span v-if="item.completed_at"> — {{ item.completed_at }}</span>
            </p>

            <p v-else>Пункт ещё не выполнен.</p>
          </div>

          <div class="organization-actions">
            <button
              type="button"
              :disabled="checklistStore.loading"
              @click="toggleChecklistItem(item)"
            >
              {{ item.is_completed ? 'Вернуть' : 'Выполнено' }}
            </button>

            <button
              v-if="canManageCurrentOrganization"
              type="button"
              :disabled="checklistStore.loading"
              @click="removeChecklistItem(item.id)"
            >
              Удалить
            </button>
          </div>
        </article>
      </div>

      <form
        v-if="canManageCurrentOrganization"
        class="form compact-form"
        @submit.prevent="submitChecklistItem"
      >
        <label>
          Новый пункт
          <input
            v-model="checklistForm.title"
            type="text"
            required
            placeholder="Например: Проверить фильтры"
          />
        </label>

        <button type="submit" :disabled="checklistStore.loading">
          {{ checklistStore.loading ? 'Добавление...' : 'Добавить пункт' }}
        </button>
      </form>

      <p v-else class="description">
        Ваша роль позволяет выполнять пункты чек-листа, но не создавать и не удалять их.
      </p>
    </section>

    <section class="card">
      <h2>Файлы заявки</h2>

      <p class="description">
        Можно загрузить фото, PDF или текстовый файл. Максимальный размер — 10 MB.
      </p>

      <p v-if="filesStore.loading">Загрузка файлов...</p>

      <div v-else-if="filesStore.files.length === 0" class="empty-state">
        <p>Файлы пока не добавлены.</p>
      </div>

      <div v-else class="file-list">
        <article v-for="file in filesStore.files" :key="file.id" class="file-item">
          <div>
            <h3>{{ file.original_name }}</h3>
            <p>{{ file.mime_type ?? 'unknown' }} · {{ formatFileSize(file.size) }}</p>
            <p v-if="file.uploaded_by">Загрузил: {{ file.uploaded_by.name }}</p>
            <p v-if="file.created_at">Дата загрузки: {{ file.created_at }}</p>
          </div>

          <div class="organization-actions">
            <button type="button" :disabled="filesStore.loading" @click="downloadFile(file.id)">
              Скачать
            </button>

            <button
              v-if="canDeleteFile(file)"
              type="button"
              :disabled="filesStore.loading"
              @click="removeFile(file)"
            >
              Удалить
            </button>
          </div>
        </article>
      </div>

      <form class="form compact-form" @submit.prevent="submitFile">
        <label>
          Загрузить файл
          <input
            ref="fileInput"
            type="file"
            required
            accept=".jpg,.jpeg,.png,.webp,.pdf,.txt"
            @change="handleFileChange"
          />
        </label>

        <button type="submit" :disabled="filesStore.loading || selectedFile === null">
          {{ filesStore.loading ? 'Загрузка...' : 'Загрузить файл' }}
        </button>
      </form>
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
            v-if="canManageCurrentOrganization"
            type="button"
            :disabled="assignmentsStore.loading"
            @click="removeAssignment(assignment.id)"
          >
            Снять
          </button>
        </article>
      </div>

      <form
        v-if="canManageCurrentOrganization"
        class="form compact-form"
        @submit.prevent="assignUser"
      >
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

      <p v-else class="description">Ваша роль позволяет смотреть назначения, но не изменять их.</p>
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
