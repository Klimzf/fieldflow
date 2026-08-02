import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type {
  ApiResource,
  PaginatedApiResourceCollection,
  PaginationMeta,
} from '@/shared/types/api'
import type { Equipment } from '@/shared/types/equipment'

export interface EquipmentListFilters {
  q?: string
  page?: number
  per_page?: number
}

export interface EquipmentPayload {
  name: string
  type?: string | null
  manufacturer?: string | null
  model?: string | null
  serial_number?: string | null
  installed_at?: string | null
  notes?: string | null
}

export const useEquipmentStore = defineStore('equipment', () => {
  const equipment = ref<Equipment[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  async function fetchEquipment(siteId: number, filters: EquipmentListFilters = {}): Promise<void> {
    loading.value = true

    const params: Record<string, string | number> = {
      page: filters.page ?? 1,
      per_page: filters.per_page ?? 10,
    }

    if (filters.q !== undefined && filters.q.trim() !== '') {
      params.q = filters.q.trim()
    }

    try {
      const response = await http.get<PaginatedApiResourceCollection<Equipment>>(
        `/api/sites/${siteId}/equipment`,
        {
          params,
        },
      )

      equipment.value = response.data.data
      pagination.value = response.data.meta
    } finally {
      loading.value = false
    }
  }

  async function createEquipment(siteId: number, payload: EquipmentPayload): Promise<Equipment> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<Equipment>>(
        `/api/sites/${siteId}/equipment`,
        payload,
      )

      const createdEquipment = response.data.data

      equipment.value.unshift(createdEquipment)

      if (pagination.value !== null) {
        pagination.value.total += 1
        pagination.value.to = pagination.value.to === null ? 1 : pagination.value.to + 1
      }

      return createdEquipment
    } finally {
      loading.value = false
    }
  }

  function clearEquipment(): void {
    equipment.value = []
    pagination.value = null
  }

  return {
    equipment,
    pagination,
    loading,
    fetchEquipment,
    createEquipment,
    clearEquipment,
  }
})
