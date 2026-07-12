import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { Client, ClientPayload } from '@/shared/types/client'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'

export const useClientsStore = defineStore('clients', () => {
  const clients = ref<Client[]>([])
  const loading = ref(false)

  async function fetchClients(organizationId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<Client>>(
        `/api/organizations/${organizationId}/clients`,
      )

      clients.value = response.data.data
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

      clients.value.push(client)

      return client
    } finally {
      loading.value = false
    }
  }

  return {
    clients,
    loading,
    fetchClients,
    createClient,
  }
})
