<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  WORK_ORDER_PRIORITY_LABELS,
  WORK_ORDER_STATUS_LABELS,
} from '@/shared/constants/work-orders'
import { useOrganizationsStore } from '@/stores/organizations'
import { useScheduleStore } from '@/stores/schedule'
import type { ScheduleWorkOrder } from '@/shared/types/schedule'

const route = useRoute()
const organizationsStore = useOrganizationsStore()
const scheduleStore = useScheduleStore()

const organizationId = computed(() => Number(route.params.organizationId))

const currentWeekStart = startOfWeek(new Date())
const currentWeekEnd = addDays(currentWeekStart, 6)

const filters = reactive({
  start: toDateInputValue(currentWeekStart),
  end: toDateInputValue(currentWeekEnd),
})

const error = ref<string | null>(null)

const organization = computed(() =>
  organizationsStore.organizations.find((item) => item.id === organizationId.value),
)

const groupedDays = computed(() => {
  const days: Array<{
    date: string
    label: string
    workOrders: ScheduleWorkOrder[]
  }> = []

  const start = parseDateInputValue(filters.start)
  const end = parseDateInputValue(filters.end)

  if (start === null || end === null || start > end) {
    return days
  }

  let currentDate = start

  while (currentDate <= end) {
    const dateKey = toDateInputValue(currentDate)

    days.push({
      date: dateKey,
      label: formatDateLabel(currentDate),
      workOrders: scheduleStore.WorkOrders.filter((workOrder) =>
        workOrder.scheduled_at?.startsWith(dateKey),
      ),
    })

    currentDate = addDays(currentDate, 1)
  }

  return days
})

onMounted(async () => {
  scheduleStore.clearSchedule()

  await organizationsStore.fetchOrganizations()
  organizationsStore.setActiveOrganization(organizationId.value)

  await loadSchedule()
})

async function loadSchedule(): Promise<void> {
  error.value = null

  if (filters.start === '' || filters.end === '') {
    error.value = 'Выберите начальную и конечную дату.'

    return
  }

  if (filters.start > filters.end) {
    error.value = 'Начальная дата не может быть позже конечной.'

    return
  }

  try {
    await scheduleStore.fetchSchedule(organizationId.value, {
      start: filters.start,
      end: filters.end,
    })
  } catch {
    error.value = 'Не удалось загрузить расписание. Попробуйте позже.'
  }
}

async function showCurrentWeek(): Promise<void> {
  const weekStart = startOfWeek(new Date())
  const weekEnd = addDays(weekStart, 6)

  filters.start = toDateInputValue(weekStart)
  filters.end = toDateInputValue(weekEnd)

  await loadSchedule()
}

async function showPreviousWeek(): Promise<void> {
  const start = parseDateInputValue(filters.start)

  if (start === null) {
    return
  }

  const previousWeekStart = addDays(start, -7)

  filters.start = toDateInputValue(previousWeekStart)
  filters.end = toDateInputValue(addDays(previousWeekStart, 6))

  await loadSchedule()
}

async function showNextWeek(): Promise<void> {
  const start = parseDateInputValue(filters.start)

  if (start === null) {
    return
  }

  const nextWeekStart = addDays(start, 7)

  filters.start = toDateInputValue(nextWeekStart)
  filters.end = toDateInputValue(addDays(nextWeekStart, 6))

  await loadSchedule()
}

function workOrderDetailPath(workOrder: ScheduleWorkOrder): string {
  return `/clients/${workOrder.client_id}/sites/${workOrder.site_id}/work-orders/${workOrder.id}`
}

function formatTime(value: string | null): string {
  if (value === null) {
    return 'Время не указано'
  }

  return new Intl.DateTimeFormat('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

function formatDateLabel(value: Date): string {
  return new Intl.DateTimeFormat('ru-RU', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
  }).format(value)
}

function toDateInputValue(value: Date): string {
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function parseDateInputValue(value: string): Date | null {
  if (value === '') {
    return null
  }

  return new Date(`${value}T00:00:00`)
}

function startOfWeek(value: Date): Date {
  const date = new Date(value)
  const day = (date.getDay() + 6) % 7

  date.setHours(0, 0, 0, 0)
  date.setDate(date.getDate() - day)

  return date
}

function addDays(value: Date, days: number): Date {
  const date = new Date(value)

  date.setDate(date.getDate() + days)

  return date
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Расписание</h1>
        <p v-if="organization" class="description">Организация: {{ organization.name }}</p>
      </div>

      <RouterLink :to="{ name: 'dashboard' }"> Вернуться в dashboard </RouterLink>
    </header>

    <section v-if="error" class="card">
      <div class="error">
        <p>{{ error }}</p>
      </div>
    </section>

    <section class="card">
      <h2>Период</h2>

      <form class="form compact-form" @submit.prevent="loadSchedule">
        <label>
          Начало
          <input v-model="filters.start" type="date" required />
        </label>

        <label>
          Конец
          <input v-model="filters.end" type="date" requierd />
        </label>

        <div class="organization-actions">
          <button type="submit" :disabled="scheduleStore.loading">Показать</button>

          <button type="button" :disabled="scheduleStore.loading" @click="showPreviousWeek">
            Предыдущая неделя
          </button>

          <button type="button" :disabled="scheduleStore.loading" @click="showCurrentWeek">
            Текущая неделя
          </button>

          <button type="button" :disabled="scheduleStore.loading" @click="showNextWeek">
            Следующая неделя
          </button>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Заявки в расписании</h2>

      <p v-if="scheduleStore.loading">Загрузка расписания...</p>

      <div v-else-if="scheduleStore.WorkOrders.length === 0" class="empty-state">
        <p>В выбранном периоде нет запланированных заявок.</p>
      </div>

      <div v-else class="schedule-days">
        <article v-for="day in groupedDays" :key="day.date" class="schedule-day">
          <h3>{{ day.label }}</h3>

          <div v-if="day.workOrders.length === 0" class="empty-state">
            <p>Нет заявок на этот день.</p>
          </div>

          <div v-else class="schedule-list">
            <article v-for="workOrder in day.workOrders" :key="workOrder.id" class="schedule-item">
              <div>
                <p class="schedule-time">
                  {{ formatTime(workOrder.scheduled_at) }}
                </p>

                <h4>{{ workOrder.title }}</h4>

                <p>
                  Статус:
                  {{ WORK_ORDER_STATUS_LABELS[workOrder.status] }}
                </p>

                <p>
                  Приоритет:
                  {{ WORK_ORDER_PRIORITY_LABELS[workOrder.priority] }}
                </p>

                <p v-if="workOrder.client">Клиент: {{ workOrder.client.name }}</p>

                <p v-if="workOrder.site">Объект: {{ workOrder.site.name }}</p>

                <p v-if="workOrder.equipment">Оборудование: {{ workOrder.equipment.name }}</p>

                <p
                  v-if="
                    workOrder.assigned_users !== undefined && workOrder.assigned_users.length > 0
                  "
                >
                  Назначены:
                  {{ workOrder.assigned_users.map((user) => user.name).join(', ') }}
                </p>
              </div>

              <RouterLink :to="workOrderDetailPath(workOrder)"> Открыть </RouterLink>
            </article>
          </div>
        </article>
      </div>
    </section>
  </main>
</template>
