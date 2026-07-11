export interface Organization {
  id: number
  name: string
  slug: string
  role: 'owner' | 'admin' | 'technician'
  created_at: string | null
  updated_at: string | null
}

export interface OrganizationPayload {
  name: string
  slug?: string
}

export interface ApiResource<T> {
  data: T
}

export interface ApiResourceCollection<T> {
  data: T[]
}
