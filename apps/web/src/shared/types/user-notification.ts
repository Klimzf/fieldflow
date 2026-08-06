import type { WorkOrder } from '@/shared/types/work-order'

export interface UserNotificationActor {
  id: number
  name: string
  email: string
}

export interface UserNotificationWorkOrder {
  id: number
  title: string
  status: WorkOrder['status']
  priority: WorkOrder['priority']
  client_id: number
  site_id: number
}

export interface UserNotification {
  id: number
  organization_id: number
  user_id: number
  actor_id: number | null
  work_order_id: number | null
  type: string
  title: string
  message: string | null
  read_at: string | null
  is_read: boolean
  actor?: UserNotificationActor | null
  work_order?: UserNotificationWorkOrder | null
  created_at: string | null
  updated_at: string | null
}
