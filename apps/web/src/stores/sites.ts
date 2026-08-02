import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type {
  ApiResource,
  PaginatedApiResourceCollection,
  PaginationMeta,
} from '@/shared/types/api'
import type { Site } from '@/shared/types/site'

export interface SiteListFilters {
  q?: string
  page?: number
  per_page?: number
}

export interface SitePayload {
  name: string
  address?: string | null
  contact_name?: string | null
  contact_phone?: string | null
  notes?: string | null
}

export const useSitesStore = defineStore('sites', () => {
  const sites = ref<Site[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  async function fetchSites(clientId: number, filters: SiteListFilters = {}): Promise<void> {
    loading.value = true

    const params: Record<string, string | number> = {
      page: filters.page ?? 1,
      per_page: filters.per_page ?? 10,
    }

    if (filters.q !== undefined && filters.q.trim() !== '') {
      params.q = filters.q.trim()
    }

    try {
      const response = await http.get<PaginatedApiResourceCollection<Site>>(
        `/api/clients/${clientId}/sites`,
        {
          params,
        },
      )

      sites.value = response.data.data
      pagination.value = response.data.meta
    } finally {
      loading.value = false
    }
  }

  async function createSite(clientId: number, payload: SitePayload): Promise<Site> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<Site>>(`/api/clients/${clientId}/sites`, payload)

      const site = response.data.data

      sites.value.unshift(site)

      if (pagination.value !== null) {
        pagination.value.total += 1
        pagination.value.to = pagination.value.to === null ? 1 : pagination.value.to + 1
      }

      return site
    } finally {
      loading.value = false
    }
  }

  function clearSites(): void {
    sites.value = []
    pagination.value = null
  }

  return {
    sites,
    pagination,
    loading,
    fetchSites,
    createSite,
    clearSites,
  }
})
