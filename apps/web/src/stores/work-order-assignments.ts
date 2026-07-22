import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { http } from '@/shared/api/http'
import type { ApiResource, ApiResourceCollection } from '@/shared/types/api'
import type {
  AssignableUser,
  WorkOrderAssignment,
  WorkOrderAssignmentPayload,
} from '@/shared/types/work-order-assignment'

export const useWorkOrderAssignmentsStore = defineStore('work-order-assignments', () => {
  const assignments = ref<WorkOrderAssignment[]>([])
  const assignableUsers = ref<AssignableUser[]>([])
  const loading = ref(false)

  const assignedUserIds = computed(() => assignments.value.map((assignment) => assignment.user_id))

  const availableAssignableUsers = computed(() =>
    assignableUsers.value.filter((user) => !assignedUserIds.value.includes(user.id)),
  )

  async function fetchAssignments(workOrderId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<WorkOrderAssignment>>(
        `/api/work-orders/${workOrderId}/assignments`,
      )

      assignments.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function fetchAssignableUsers(workOrderId: number): Promise<void> {
    loading.value = true

    try {
      const response = await http.get<ApiResourceCollection<AssignableUser>>(
        `/api/work-orders/${workOrderId}/assignable-users`,
      )

      assignableUsers.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  async function createAssignment(
    workOrderId: number,
    payload: WorkOrderAssignmentPayload,
  ): Promise<WorkOrderAssignment> {
    loading.value = true

    try {
      const response = await http.post<ApiResource<WorkOrderAssignment>>(
        `/api/work-orders/${workOrderId}/assignments`,
        payload,
      )

      const assignment = response.data.data

      assignments.value.push(assignment)

      return assignment
    } finally {
      loading.value = false
    }
  }

  async function deleteAssignment(assignmentId: number): Promise<void> {
    loading.value = true

    try {
      await http.delete(`/api/work-order-assignments/${assignmentId}`)

      assignments.value = assignments.value.filter((assignment) => assignment.id !== assignmentId)
    } finally {
      loading.value = false
    }
  }

  return {
    assignments,
    assignableUsers,
    availableAssignableUsers,
    loading,
    fetchAssignments,
    fetchAssignableUsers,
    createAssignment,
    deleteAssignment,
  }
})
