import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type {
  ApiResource,
  PaginatedApiResourceCollection,
  PaginationMeta,
} from '@/shared/types/api'
import type { WorkOrder } from '@/shared/types/work-order'

export interface WorkOrderListFilters {
  q?: string
  status?: string
  priority?: string
  page?: number
  per_page?: number
}

export interface WorkOrderPayload {
  equipment_id?: number | null
  title: string
  description?: string | null
  status: WorkOrder['status']
  priority: WorkOrder['priority']
  scheduled_at?: string | null
}

export type UpdateWorkOrderPayload = Partial<WorkOrderPayload>

export const useWorkOrdersStore = defineStore('work-orders', () => {
  const workOrders = ref<WorkOrder[]>([])
  const currentWorkOrder = ref<WorkOrder | null>(null)
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  async function fetchWorkOrders(
    siteId: number,
    filters: WorkOrderListFilters = {},
  ): Promise<void> {
    loading.value = true

    const params: Record<string, string | number> = {
      page: filters.page ?? 1,
      per_page: filters.per_page ?? 10,
    }

    if (filters.q !== undefined && filters.q.trim() !== '') {
      params.q = filters.q.trim()
    }

    if (filters.status !== undefined && filters.status !== '') {
      params.status = filters.status
    }

    if (filters.priority !== undefined && filters.priority !== '') {
      params.priority = filters.priority
    }

    try {
      const response = await http.get<PaginatedApiResourceCollection<WorkOrder>>(
        `/api/sites/${siteId}/work-orders`,
        {
          params,
        },
      )

      workOrders.value = response.data.data
      pagination.value = response.data.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchWorkOrder(workOrderId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResource<WorkOrder>>(`/api/work-orders/${workOrderId}`)

      currentWorkOrder.value = response.data.data
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

      if (pagination.value !== null) {
        pagination.value.total += 1
        pagination.value.to = pagination.value.to === null ? 1 : pagination.value.to + 1
      }

      return workOrder
    } finally {
      loading.value = false
    }
  }

  async function updateWorkOrder(
    workOrderId: number,
    payload: UpdateWorkOrderPayload,
  ): Promise<WorkOrder> {
    loading.value = true

    try {
      const response = await http.patch<ApiResource<WorkOrder>>(
        `/api/work-orders/${workOrderId}`,
        payload,
      )

      const updatedWorkOrder = response.data.data

      currentWorkOrder.value = updatedWorkOrder

      workOrders.value = workOrders.value.map((workOrder) =>
        workOrder.id === updatedWorkOrder.id ? updatedWorkOrder : workOrder,
      )

      return updatedWorkOrder
    } finally {
      loading.value = false
    }
  }

  function clearCurrentWorkOrder(): void {
    currentWorkOrder.value = null
  }

  function clearWorkOrders(): void {
    workOrders.value = []
    pagination.value = null
  }

  async function downloadServiceReport(workOrderId: number): Promise<void> {
    const response = await http.get(`/api/work-orders/${workOrderId}/service-report/download`, {
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')

    link.href = url
    link.download = `service-report-work-order-${workOrderId}.pdf`

    document.body.appendChild(link)
    link.click()
    link.remove()

    window.URL.revokeObjectURL(url)
  }

  return {
    workOrders,
    currentWorkOrder,
    pagination,
    loading,
    fetchWorkOrders,
    fetchWorkOrder,
    createWorkOrder,
    updateWorkOrder,
    downloadServiceReport,
    clearCurrentWorkOrder,
    clearWorkOrders,
  }
})
