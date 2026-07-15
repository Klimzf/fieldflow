export interface Site {
  id: number
  organization_id: number
  client_id: number
  name: string
  address: string | null
  contact_name: string | null
  contact_phone: string | null
  notes: string | null
  created_at: string | null
  updated_at: string | null
}

export interface SitePayload {
  name: string
  address?: string
  contact_name?: string
  contact_phone?: string
  notes?: string
}
