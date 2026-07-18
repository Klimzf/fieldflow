export type WorkOrderUpdateType = 'comment' | 'created' | 'status_changed'

export interface WorkOrderUpdateUser {
  id: number
  name: string
}

export interface WorkOrderUpdate {
  id: number
  organization_id: number
  work_order_id: number
  user_id: number | null
  type: WorkOrderUpdateType
  message: string | null
  old_status: string | null
  new_status: string | null
  user?: WorkOrderUpdateUser | null
  created_at: string | null
  updated_at: string | null
}

export interface WorkOrderUpdatePayload {
  message: string
}
