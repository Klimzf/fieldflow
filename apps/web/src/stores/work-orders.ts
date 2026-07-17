import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type { WorkOrder, WorkOrderPayload } from '@/shared/types/work-order'

export const useWorkOrdersStore = defineStore('work-orders', () => {
  const workOrders = ref<WorkOrder[]>([])
  const loading = ref(false)

  async function fetchWorkOrders(siteId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<WorkOrder>>(
        `/api/sites/${siteId}/work-orders`,
      )

      workOrders.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function createWorkOrder(siteId: number, payload: WorkOrderPayload): Promise<WorkOrder> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<WorkOrder>>(
        `/api/sites/${siteId}/work-orders`,
        payload,
      )

      const workOrder = response.data.data

      workOrders.value.unshift(workOrder)

      return workOrder
    } finally {
      loading.value = false
    }
  }

  return {
    workOrders,
    loading,
    fetchWorkOrders,
    createWorkOrder,
  }
})
