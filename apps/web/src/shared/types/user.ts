export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string | null
}

export interface ApiResource<T> {
  data: T
}
