import type { WorkOrder, WorkOrderStatus } from '@/shared/types/work-order'

export interface OrganizationDashboard {
  clients_count: number
  sites_count: number
  equipment_count: number
  work_orders_count: number
  work_orders_by_status: Record<WorkOrderStatus, number>
  urgent_work_orders_count: number
  assigned_to_me_count: number
  latest_work_orders: WorkOrder[]
  assigned_to_me_work_orders: WorkOrder[]
}
