import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type { WorkOrderUpdate, WorkOrderUpdatePayload } from '@/shared/types/work-order-update'

export const useWorkOrderUpdatesStore = defineStore('work-order-updates', () => {
  const updates = ref<WorkOrderUpdate[]>([])
  const loading = ref(false)

  async function fetchUpdates(workOrderId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<WorkOrderUpdate>>(
        `/api/work-orders/${workOrderId}/updates`,
      )

      updates.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function createUpdate(
    workOrderId: number,
    payload: WorkOrderUpdatePayload,
  ): Promise<WorkOrderUpdate> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<WorkOrderUpdate>>(
        `/api/work-orders/${workOrderId}/updates`,
        payload,
      )

      const update = response.data.data

      updates.value.push(update)

      return update
    } finally {
      loading.value = false
    }
  }

  return {
    updates,
    loading,
    fetchUpdates,
    createUpdate,
  }
})
