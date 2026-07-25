import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type {
  UpdateWorkOrderChecklistItemCompletionPayload,
  WorkOrderChecklistItem,
  WorkOrderChecklistItemPayload,
} from '@/shared/types/work-order-checklist-item'

export const useWorkOrderChecklistItemsStore = defineStore('work-order-checklist-items', () => {
  const items = ref<WorkOrderChecklistItem[]>([])
  const loading = ref(false)

  const completedCount = computed(() => items.value.filter((item) => item.is_completed).length)

  const totalCount = computed(() => items.value.length)

  async function fetchItems(workOrderId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<WorkOrderChecklistItem>>(
        `/api/work-orders/${workOrderId}/checklist-items`,
      )

      items.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function createItem(
    workOrderId: number,
    payload: WorkOrderChecklistItemPayload,
  ): Promise<WorkOrderChecklistItem> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<WorkOrderChecklistItem>>(
        `/api/work-orders/${workOrderId}/checklist-items`,
        payload,
      )

      const item = response.data.data

      items.value.push(item)
      sortItems()

      return item
    } finally {
      loading.value = false
    }
  }

  async function updateCompletion(
    itemId: number,
    payload: UpdateWorkOrderChecklistItemCompletionPayload,
  ): Promise<WorkOrderChecklistItem> {
    loading.value = true

    try {
      const response = await http.patch<ApiResource<WorkOrderChecklistItem>>(
        `/api/work-order-checklist-items/${itemId}/completion`,
        payload,
      )

      const item = response.data.data

      items.value = items.value.map((currentItem) =>
        currentItem.id === item.id ? item : currentItem,
      )

      sortItems()

      return item
    } finally {
      loading.value = false
    }
  }

  async function deleteItem(itemId: number): Promise<void> {
    loading.value = true

    try {
      await http.delete(`/api/work-order-checklist-items/${itemId}`)

      items.value = items.value.filter((item) => item.id !== itemId)
    } finally {
      loading.value = false
    }
  }

  function sortItems(): void {
    items.value = [...items.value].sort((first, second) => {
      if (first.position !== second.position) {
        return first.position - second.position
      }

      return first.id - second.id
    })
  }

  return {
    items,
    loading,
    completedCount,
    totalCount,
    fetchItems,
    createItem,
    updateCompletion,
    deleteItem,
  }
})
