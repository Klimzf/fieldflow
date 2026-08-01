<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getValidationError } from '@/shared/api/errors'
import {
  WORK_ORDER_PRIORITIES,
  WORK_ORDER_STATUS_LABELS,
  WORK_ORDER_STATUSES,
} from '@/shared/constants/work-orders'
import { useWorkOrdersStore } from '@/stores/work-orders'
import type { WorkOrder } from '@/shared/types/work-order'

const route = useRoute()
const workOrdersStore = useWorkOrdersStore()

const clientId = computed(() => Number(route.params.clientId))
const siteId = computed(() => Number(route.params.siteId))

const filters = reactive({
  q: '',
  status: '',
  priority: '',
  per_page: 10,
})

const form = reactive({
  equipment_id: null as number | null,
  title: '',
  description: '',
  status: 'new' as WorkOrder['status'],
  priority: 'medium' as WorkOrder['priority'],
  scheduled_at: '',
})

const error = ref<string | null>(null)
const validationErrors = ref<string[]>([])

const priorityLabels: Record<WorkOrder['priority'], string> = {
  low: 'Низкий',
  medium: 'Средний',
  high: 'Высокий',
  urgent: 'Срочный',
}

onMounted(async () => {
  await loadFirstPage()
})

async function loadFirstPage(): Promise<void> {
  await workOrdersStore.fetchWorkOrders(siteId.value, {
    page: 1,
    per_page: filters.per_page,
  })
}

async function applyFilters(): Promise<void> {
  clearErrors()

  try {
    await workOrdersStore.fetchWorkOrders(siteId.value, {
      q: filters.q,
      status: filters.status,
      priority: filters.priority,
      per_page: filters.per_page,
      page: 1,
    })
  } catch {
    error.value = 'Не удалось загрузить заявки. Попробуйте позже.'
  }
}

async function resetFilters(): Promise<void> {
  filters.q = ''
  filters.status = ''
  filters.priority = ''
  filters.per_page = 10

  await applyFilters()
}

async function goToPage(page: number): Promise<void> {
  clearErrors()

  try {
    await workOrdersStore.fetchWorkOrders(siteId.value, {
      q: filters.q,
      status: filters.status,
      priority: filters.priority,
      per_page: filters.per_page,
      page,
    })
  } catch {
    error.value = 'Не удалось загрузить страницу заявок. Попробуйте позже.'
  }
}

async function submit(): Promise<void> {
  clearErrors()

  try {
    await workOrdersStore.createWorkOrder(siteId.value, {
      equipment_id: form.equipment_id,
      title: form.title,
      description: form.description || null,
      status: form.status,
      priority: form.priority,
      scheduled_at: form.scheduled_at || null,
    })

    form.equipment_id = null
    form.title = ''
    form.description = ''
    form.status = 'new'
    form.priority = 'medium'
    form.scheduled_at = ''

    await applyFilters()
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

function clearErrors(): void {
  error.value = null
  validationErrors.value = []
}

function formatPriority(priority: WorkOrder['priority']): string {
  return priorityLabels[priority] ?? priority
}

function workOrderDetailPath(workOrderId: number): string {
  return `/clients/${clientId.value}/sites/${siteId.value}/work-orders/${workOrderId}`
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Заявки</h1>
      </div>

      <RouterLink :to="{ name: 'client.sites', params: { clientId } }">
        Назад к объектам
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
      <h2>Создать заявку</h2>

      <form class="form" @submit.prevent="submit">
        <label>
          Название
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="Например: HVAC is not cooling"
          />
        </label>

        <label>
          Описание
          <textarea v-model="form.description" rows="4" placeholder="Опишите проблему или задачу" />
        </label>

        <label>
          Оборудование ID
          <input
            v-model.number="form.equipment_id"
            type="number"
            min="1"
            placeholder="Можно оставить пустым"
          />
        </label>

        <label>
          Статус
          <select v-model="form.status">
            <option v-for="status in WORK_ORDER_STATUSES" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </label>

        <label>
          Приоритет
          <select v-model="form.priority">
            <option
              v-for="priority in WORK_ORDER_PRIORITIES"
              :key="priority.value"
              :value="priority.value"
            >
              {{ priority.label }}
            </option>
          </select>
        </label>

        <label>
          Запланировано
          <input v-model="form.scheduled_at" type="datetime-local" />
        </label>

        <button type="submit" :disabled="workOrdersStore.loading">
          {{ workOrdersStore.loading ? 'Создание...' : 'Создать заявку' }}
        </button>
      </form>
    </section>

    <section class="card">
      <h2>Поиск и фильтры</h2>

      <form class="form compact-form" @submit.prevent="applyFilters">
        <label>
          Поиск
          <input v-model="filters.q" type="search" placeholder="Название или описание заявки" />
        </label>

        <label>
          Статус
          <select v-model="filters.status">
            <option value="">Все статусы</option>

            <option v-for="status in WORK_ORDER_STATUSES" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </label>

        <label>
          Приоритет
          <select v-model="filters.priority">
            <option value="">Все приоритеты</option>

            <option
              v-for="priority in WORK_ORDER_PRIORITIES"
              :key="priority.value"
              :value="priority.value"
            >
              {{ priority.label }}
            </option>
          </select>
        </label>

        <label>
          На странице
          <select v-model.number="filters.per_page">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </label>

        <div class="organization-actions">
          <button type="submit" :disabled="workOrdersStore.loading">Применить</button>

          <button type="button" :disabled="workOrdersStore.loading" @click="resetFilters">
            Сбросить
          </button>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Список заявок</h2>

      <p v-if="workOrdersStore.loading">Загрузка заявок...</p>

      <div v-else-if="workOrdersStore.workOrders.length === 0" class="empty-state">
        <p>Заявок пока нет.</p>
      </div>

      <div v-else class="organization-list">
        <article
          v-for="workOrder in workOrdersStore.workOrders"
          :key="workOrder.id"
          class="organization-item"
        >
          <div>
            <h3>{{ workOrder.title }}</h3>

            <p>Статус: {{ WORK_ORDER_STATUS_LABELS[workOrder.status] }}</p>
            <p>Приоритет: {{ formatPriority(workOrder.priority) }}</p>

            <p v-if="workOrder.description">
              {{ workOrder.description }}
            </p>

            <p v-if="workOrder.equipment_id">Оборудование ID: {{ workOrder.equipment_id }}</p>

            <p v-if="workOrder.scheduled_at">Запланировано: {{ workOrder.scheduled_at }}</p>
          </div>

          <RouterLink :to="workOrderDetailPath(workOrder.id)"> Открыть </RouterLink>
        </article>
      </div>

      <div v-if="workOrdersStore.pagination" class="pagination">
        <p>
          Показано
          {{ workOrdersStore.pagination.from ?? 0 }}–{{ workOrdersStore.pagination.to ?? 0 }} из
          {{ workOrdersStore.pagination.total }}
        </p>

        <div class="organization-actions">
          <button
            type="button"
            :disabled="workOrdersStore.loading || workOrdersStore.pagination.current_page <= 1"
            @click="goToPage(workOrdersStore.pagination.current_page - 1)"
          >
            Назад
          </button>

          <span>
            Страница {{ workOrdersStore.pagination.current_page }} из
            {{ workOrdersStore.pagination.last_page }}
          </span>

          <button
            type="button"
            :disabled="
              workOrdersStore.loading ||
              workOrdersStore.pagination.current_page >= workOrdersStore.pagination.last_page
            "
            @click="goToPage(workOrdersStore.pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
