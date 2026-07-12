export interface Client {
  id: number
  organization_id: number
  name: string
  email: string | null
  phone: string | null
  address: string | null
  notes: string | null
  created_at: string | null
  updated_at: string | null
}

export interface ClientPayload {
  name: string
  email?: string
  phone?: string
  address?: string
  notes?: string
}
