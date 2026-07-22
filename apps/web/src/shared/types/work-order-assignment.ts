export type AssignableUserRole = 'owner' | 'admin' | 'technician'

export interface AssignableUser {
  id: number
  name: string
  email: string
  role: AssignableUserRole
}

export interface WorkOrderAssignmentUser {
  id: number
  name: string
  email: string
}

export interface WorkOrderAssignment {
  id: number
  organization_id: number
  work_order_id: number
  user_id: number
  assigned_by_id: number | null
  user?: WorkOrderAssignmentUser
  assigned_by?: WorkOrderAssignmentUser | null
  created_at: string | null
  updated_at: string | null
}

export interface WorkOrderAssignmentPayload {
  user_id: number
}
