import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { http } from '@/shared/api/http'
import type {
  ApiResource,
  PaginatedApiResourceCollection,
  PaginationMeta,
} from '@/shared/types/api'
import type { UserNotification } from '@/shared/types/user-notification'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<UserNotification[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  const unreadCount = computed(
    () => notifications.value.filter((notification) => !notification.is_read).length,
  )

  async function fetchNotifications(page = 1): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<PaginatedApiResourceCollection<UserNotification>>(
        '/api/notifications',
        {
          params: {
            page,
          },
        },
      )

      notifications.value = response.data.data
      pagination.value = response.data.meta
    } finally {
      loading.value = false
    }
  }

  async function markAsRead(notificationId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.patch<ApiResource<UserNotification>>(
        `/api/notifications/${notificationId}/read`,
      )

      const updatedNotification = response.data.data

      notifications.value = notifications.value.map((notification) =>
        notification.id === updatedNotification.id ? updatedNotification : notification,
      )
    } finally {
      loading.value = false
    }
  }

  async function markAllAsRead(): Promise<void> {
    loading.value = true

    try {
      await http.patch('/api/notifications/read-all')

      notifications.value = notifications.value.map((notification) => ({
        ...notification,
        is_read: true,
        read_at: notification.read_at ?? new Date().toISOString(),
      }))
    } finally {
      loading.value = false
    }
  }

  function clearNotifications(): void {
    notifications.value = []
    pagination.value = null
  }

  return {
    notifications,
    pagination,
    loading,
    unreadCount,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    clearNotifications,
  }
})
