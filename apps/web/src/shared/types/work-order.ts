export type WorkOrderStatus = 'new' | 'in_progress' | 'completed' | 'cancelled'

export type WorkOrderPriority = 'low' | 'medium' | 'high' | 'urgent'

export interface WorkOrder {
  id: number
  organization_id: number
  client_id: number
  site_id: number
  equipment_id: number | null
  title: string
  description: string | null
  status: WorkOrderStatus
  priority: WorkOrderPriority
  scheduled_at: string | null
  completed_at: string | null
  created_at: string | null
  updated_at: string | null
}

export interface WorkOrderPayload {
  title: string
  description?: string
  status?: WorkOrderStatus
  priority?: WorkOrderPriority
  equipment_id?: number
  scheduled_at?: string
}
