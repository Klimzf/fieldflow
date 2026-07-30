export interface WorkOrderFileUser {
  id: number
  name: string
  email: string
}

export interface WorkOrderFile {
  id: number
  organization_id: number
  work_order_id: number
  uploaded_by_id: number | null
  original_name: string
  mime_type: string | null
  size: number
  download_url: string
  uploaded_by?: WorkOrderFileUser | null
  created_at: string | null
  updated_at: string | null
}
