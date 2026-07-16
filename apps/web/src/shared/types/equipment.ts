export interface Equipment {
  id: number
  organization_id: number
  client_id: number
  site_id: number
  name: string
  type: string | null
  manufacturer: string | null
  model: string | null
  serial_number: string | null
  installed_at: string | null
  notes: string | null
  created_at: string | null
  updated_at: string | null
}

export interface EquipmentPayload {
  name: string
  type?: string
  manufacturer?: string
  model?: string
  serial_number?: string
  installed_at?: string
  notes?: string
}
