import type { WorkOrder } from './work-order'

export interface ScheduleAssignedUser {
  id: number
  name: string
  email: string
}

export interface ScheduleClient {
  id: number
  name: string
}

export interface ScheduleSite {
  id: number
  name: string
  address: string | null
}

export interface ScheduleEquipment {
  id: number
  name: string
}

export interface ScheduleWorkOrder {
  id: number
  organization_id: number
  client_id: number
  site_id: number
  equipment_id: number | null
  title: string
  description: string | null
  status: WorkOrder['status']
  priority: WorkOrder['priority']
  scheduled_at: string | null
  completed_at: string | null
  client?: ScheduleClient | null
  site?: ScheduleSite | null
  equipment?: ScheduleEquipment | null
  assigned_users?: ScheduleAssignedUser[]
  created_at: string | null
  updated_at: string | null
}
