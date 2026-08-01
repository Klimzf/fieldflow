import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type {
  ApiResource,
  PaginatedApiResourceCollection,
  PaginationMeta,
} from '@/shared/types/api'
import type { Client } from '@/shared/types/client'

export interface ClientListFilters {
  q?: string
  page?: number
  per_page?: number
}

export interface ClientPayload {
  name: string
  email?: string | null
  phone?: string | null
  address?: string | null
  notes?: string | null
}

export const useClientsStore = defineStore('clients', () => {
  const clients = ref<Client[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)

  async function fetchClients(
    organizationId: number,
    filters: ClientListFilters = {},
  ): Promise<void> {
    loading.value = true

    const params: Record<string, string | number> = {
      page: filters.page ?? 1,
      per_page: filters.per_page ?? 10,
    }

    if (filters.q !== undefined && filters.q.trim() !== '') {
      params.q = filters.q.trim()
    }

    try {
      const response = await http.get<PaginatedApiResourceCollection<Client>>(
        `/api/organizations/${organizationId}/clients`,
        {
          params,
        },
      )

      clients.value = response.data.data
      pagination.value = response.data.meta
    } finally {
      loading.value = false
    }
  }

  async function createClient(organizationId: number, payload: ClientPayload): Promise<Client> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<Client>>(
        `/api/organizations/${organizationId}/clients`,
        payload,
      )

      const client = response.data.data

      clients.value.unshift(client)

      if (pagination.value !== null) {
        pagination.value.total += 1
        pagination.value.to = pagination.value.to === null ? 1 : pagination.value.to + 1
      }

      return client
    } finally {
      loading.value = false
    }
  }

  function clearClients(): void {
    clients.value = []
    pagination.value = null
  }

  return {
    clients,
    pagination,
    loading,
    fetchClients,
    createClient,
    clearClients,
  }
})
