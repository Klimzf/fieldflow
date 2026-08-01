import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { http } from '@/shared/api/http'
import { canManageRole } from '@/shared/constants/organization-roles'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type { Organization, OrganizationPayload } from '@/shared/types/organization'

export const useOrganizationsStore = defineStore('organizations', () => {
  const organizations = ref<Organization[]>([])
  const activeOrganizationId = ref<number | null>(null)
  const loading = ref(false)

  const activeOrganization = computed(
    () =>
      organizations.value.find((organization) => organization.id === activeOrganizationId.value) ??
      null,
  )

  const activeOrganizationRole = computed(() => activeOrganization.value?.role ?? null)

  const canManageActiveOrganization = computed(() => canManageRole(activeOrganizationRole.value))

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

  function organizationRole(organizationId: number): Organization['role'] | null {
    return (
      organizations.value.find((organization) => organization.id === organizationId)?.role ?? null
    )
  }

  function canManageOrganization(organizationId: number): boolean {
    return canManageRole(organizationRole(organizationId))
  }

  return {
    organizations,
    activeOrganizationId,
    activeOrganization,
    activeOrganizationRole,
    canManageActiveOrganization,
    loading,
    fetchOrganizations,
    createOrganization,
    setActiveOrganization,
    organizationRole,
    canManageOrganization,
  }
})
