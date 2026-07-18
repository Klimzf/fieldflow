import type { WorkOrderPriority, WorkOrderStatus } from '@/shared/types/work-order'

export const WORK_ORDER_STATUSES: Array<{ value: WorkOrderStatus; label: string }> = [
  { value: 'new', label: 'Новая' },
  { value: 'in_progress', label: 'В работе' },
  { value: 'completed', label: 'Выполнена' },
  { value: 'cancelled', label: 'Отменена' },
]

export const WORK_ORDER_PRIORITIES: Array<{ value: WorkOrderPriority; label: string }> = [
  { value: 'low', label: 'Низкий' },
  { value: 'medium', label: 'Средний' },
  { value: 'high', label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' },
]

export const WORK_ORDER_STATUS_LABELS: Record<WorkOrderStatus, string> = {
  new: 'Новая',
  in_progress: 'В работе',
  completed: 'Выполнена',
  cancelled: 'Отменена',
}

export const WORK_ORDER_PRIORITY_LABELS: Record<WorkOrderPriority, string> = {
  low: 'Низкий',
  medium: 'Средний',
  high: 'Высокий',
  urgent: 'Срочный',
}
