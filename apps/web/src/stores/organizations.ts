import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { http } from '@/shared/api/http'
import type { Organization, OrganizationPayload } from '@/shared/types/organization'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'

export const useOrganizationsStore = defineStore('organizations', () => {
  const organizations = ref<Organization[]>([])
  const activeOrganizationId = ref<number | null>(null)
  const loading = ref(false)

  const activeOrganization = computed(() => {
    return (
      organizations.value.find((organization) => organization.id === activeOrganizationId.value) ??
      null
    )
  })

  async function fetchOrganizations(): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<Organization>>('/api/organizations')

      organizations.value = response.data.data

      const firstOrganization = organizations.value.at(0)

      if (activeOrganizationId.value === null && firstOrganization !== undefined) {
        activeOrganizationId.value = firstOrganization.id
      }
    } finally {
      loading.value = false
    }
  }

  async function createOrganization(payload: OrganizationPayload): Promise<Organization> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<Organization>>('/api/organizations', payload)
      const organization = response.data.data

      organizations.value.push(organization)
      activeOrganizationId.value = organization.id

      return organization
    } finally {
      loading.value = false
    }
  }

  function setActiveOrganization(organizationId: number): void {
    activeOrganizationId.value = organizationId
  }

  return {
    organizations,
    activeOrganizationId,
    activeOrganization,
    loading,
    fetchOrganizations,
    createOrganization,
    setActiveOrganization,
  }
})
