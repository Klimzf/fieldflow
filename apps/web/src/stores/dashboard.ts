import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource } from '@/shared/types/api'
import type { OrganizationDashboard } from '@/shared/types/dashboard'

export const useDashboardStore = defineStore('dashboard', () => {
  const dashboard = ref<OrganizationDashboard | null>(null)
  const loading = ref(false)

  async function fetchDashboard(organizationId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResource<OrganizationDashboard>>(
        `/api/organizations/${organizationId}/dashboard`,
      )

      dashboard.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  function clearDashboard(): void {
    dashboard.value = null
  }

  return {
    dashboard,
    loading,
    fetchDashboard,
    clearDashboard,
  }
})
