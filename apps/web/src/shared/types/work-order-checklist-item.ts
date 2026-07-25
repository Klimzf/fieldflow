export interface WorkOrderChecklistItemUser {
  id: number
  name: string
  email: string
}

export interface WorkOrderChecklistItem {
  id: number
  organization_id: number
  work_order_id: number
  title: string
  is_completed: boolean
  completed_by_id: number | null
  completed_by?: WorkOrderChecklistItemUser | null
  completed_at: string | null
  position: number
  created_at: string | null
  updated_at: string | null
}

export interface WorkOrderChecklistItemPayload {
  title: string
  position?: number
}

export interface UpdateWorkOrderChecklistItemCompletionPayload {
  is_completed: boolean
}
