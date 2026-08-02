import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResourceCollection } from '@/shared/types/api'
import type { ScheduleWorkOrder } from '@/shared/types/schedule'

export interface ScheduleFilters {
  start: string
  end: string
}

export const useScheduleStore = defineStore('schedule', () => {
  const WorkOrders = ref<ScheduleWorkOrder[]>([])
  const loading = ref(false)

  async function fetchSchedule(organizationId: number, filters: ScheduleFilters): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<ScheduleWorkOrder>>(
        `/api/organizations/${organizationId}/schedule`,
        {
          params: {
            start: filters.start,
            end: filters.end,
          },
        },
      )
      WorkOrders.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  function clearSchedule(): void {
    WorkOrders.value = []
  }

  return {
    WorkOrders,
    loading,
    fetchSchedule,
    clearSchedule,
  }
})
