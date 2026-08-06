<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  WORK_ORDER_PRIORITY_LABELS,
  WORK_ORDER_STATUS_LABELS,
} from '@/shared/constants/work-orders'
import { useNotificationsStore } from '@/stores/notifications'
import type { UserNotification } from '@/shared/types/user-notification'

const notificationsStore = useNotificationsStore()

const error = ref<string | null>(null)

onMounted(async () => {
  notificationsStore.clearNotifications()

  await loadNotifications(1)
})

async function loadNotifications(page: number): Promise<void> {
  error.value = null

  try {
    await notificationsStore.fetchNotifications(page)
  } catch {
    error.value = 'Не удалось загрузить уведомления. Попробуйте позже.'
  }
}

async function markAsRead(notification: UserNotification): Promise<void> {
  if (notification.is_read) {
    return
  }

  error.value = null

  try {
    await notificationsStore.markAsRead(notification.id)
  } catch {
    error.value = 'Не удалось отметить уведомление прочитанным. Попробуйте позже.'
  }
}

async function markAllAsRead(): Promise<void> {
  error.value = null

  try {
    await notificationsStore.markAllAsRead()
  } catch {
    error.value = 'Не удалось отметить уведомления прочитанными. Попробуйте позже.'
  }
}

function workOrderDetailPath(notification: UserNotification): string | null {
  if (notification.work_order === undefined || notification.work_order === null) {
    return null
  }

  return `/clients/${notification.work_order.client_id}/sites/${notification.work_order.site_id}/work-orders/${notification.work_order.id}`
}

function formatDate(value: string | null): string {
  if (value === null) {
    return 'Дата не указана'
  }

  return new Intl.DateTimeFormat('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}
</script>

<template>
  <main class="page">
    <header class="page-header">
      <div>
        <p class="eyebrow">FieldFlow</p>
        <h1>Уведомления</h1>
        <p class="description">Непрочитанные: {{ notificationsStore.unreadCount }}</p>
      </div>

      <RouterLink :to="{ name: 'dashboard' }"> Вернуться в dashboard </RouterLink>
    </header>

    <section v-if="error" class="card">
      <div class="error">
        <p>{{ error }}</p>
      </div>
    </section>

    <section class="card">
      <div class="page-header compact-header">
        <div>
          <h2>Центр уведомлений</h2>
          <p class="description">Здесь отображаются события по заявкам, которые относятся к вам.</p>
        </div>

        <button
          type="button"
          :disabled="notificationsStore.loading || notificationsStore.unreadCount === 0"
          @click="markAllAsRead"
        >
          Отметить все прочитанными
        </button>
      </div>

      <p v-if="notificationsStore.loading">Загрузка уведомлений...</p>

      <div v-else-if="notificationsStore.notifications.length === 0" class="empty-state">
        <p>Уведомлений пока нет.</p>
      </div>

      <div v-else class="notification-list">
        <article
          v-for="notification in notificationsStore.notifications"
          :key="notification.id"
          class="notification-item"
          :class="{ unread: !notification.is_read }"
        >
          <div>
            <p class="notification-meta">
              {{ formatDate(notification.created_at) }}
              <span v-if="notification.actor"> · {{ notification.actor.name }} </span>
            </p>

            <h3>{{ notification.title }}</h3>

            <p v-if="notification.message">
              {{ notification.message }}
            </p>

            <div v-if="notification.work_order" class="notification-work-order">
              <p>Заявка: {{ notification.work_order.title }}</p>

              <p>
                Статус:
                {{ WORK_ORDER_STATUS_LABELS[notification.work_order.status] }}
              </p>

              <p>
                Приоритет:
                {{ WORK_ORDER_PRIORITY_LABELS[notification.work_order.priority] }}
              </p>
            </div>
          </div>

          <div class="organization-actions">
            <RouterLink
              v-if="workOrderDetailPath(notification)"
              :to="workOrderDetailPath(notification)!"
            >
              Открыть заявку
            </RouterLink>

            <button
              v-if="!notification.is_read"
              type="button"
              :disabled="notificationsStore.loading"
              @click="markAsRead(notification)"
            >
              Прочитано
            </button>
          </div>
        </article>
      </div>

      <div v-if="notificationsStore.pagination" class="pagination">
        <p>
          Показано
          {{ notificationsStore.pagination.from ?? 0 }}–{{
            notificationsStore.pagination.to ?? 0
          }}
          из {{ notificationsStore.pagination.total }}
        </p>

        <div class="organization-actions">
          <button
            type="button"
            :disabled="
              notificationsStore.loading || notificationsStore.pagination.current_page <= 1
            "
            @click="loadNotifications(notificationsStore.pagination.current_page - 1)"
          >
            Назад
          </button>

          <span>
            Страница {{ notificationsStore.pagination.current_page }} из
            {{ notificationsStore.pagination.last_page }}
          </span>

          <button
            type="button"
            :disabled="
              notificationsStore.loading ||
              notificationsStore.pagination.current_page >= notificationsStore.pagination.last_page
            "
            @click="loadNotifications(notificationsStore.pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
