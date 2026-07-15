import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type { Site, SitePayload } from '@/shared/types/site'

export const useSitesStore = defineStore('sites', () => {
  const sites = ref<Site[]>([])
  const loading = ref(false)

  async function fetchSites(clientId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<Site>>(`/api/clients/${clientId}/sites`)

      sites.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function createSite(clientId: number, payload: SitePayload): Promise<Site> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<Site>>(`/api/clients/${clientId}/sites`, payload)

      const site = response.data.data

      sites.value.push(site)

      return site
    } finally {
      loading.value = false
    }
  }

  return {
    sites,
    loading,
    fetchSites,
    createSite,
  }
})
