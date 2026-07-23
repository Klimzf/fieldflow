import { defineStore } from 'pinia'
import { ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type {
  AddOrganizationMemberPayload,
  OrganizationMember,
  UpdateOrganizationMemberPayload,
} from '@/shared/types/organization-member'

export const useOrganizationMembersStore = defineStore('organization-members', () => {
  const members = ref<OrganizationMember[]>([])
  const loading = ref(false)

  async function fetchMembers(organizationId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<OrganizationMember>>(
        `/api/organizations/${organizationId}/members`,
      )

      members.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function addMember(
    organizationId: number,
    payload: AddOrganizationMemberPayload,
  ): Promise<OrganizationMember> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<OrganizationMember>>(
        `/api/organizations/${organizationId}/members`,
        payload,
      )

      const member = response.data.data

      members.value.push(member)
      members.value = [...members.value].sort((first, second) =>
        first.name.localeCompare(second.name),
      )

      return member
    } finally {
      loading.value = false
    }
  }

  async function updateMemberRole(
    organizationId: number,
    memberId: number,
    payload: UpdateOrganizationMemberPayload,
  ): Promise<OrganizationMember> {
    loading.value = true

    try {
      const response = await http.patch<ApiResource<OrganizationMember>>(
        `/api/organizations/${organizationId}/members/${memberId}`,
        payload,
      )

      const member = response.data.data

      members.value = members.value.map((item) => (item.id === member.id ? member : item))

      return member
    } finally {
      loading.value = false
    }
  }

  async function removeMember(organizationId: number, memberId: number): Promise<void> {
    loading.value = true

    try {
      await http.delete(`/api/organizations/${organizationId}/members/${memberId}`)

      members.value = members.value.filter((member) => member.id !== memberId)
    } finally {
      loading.value = false
    }
  }

  return {
    members,
    loading,
    fetchMembers,
    addMember,
    updateMemberRole,
    removeMember,
  }
})
