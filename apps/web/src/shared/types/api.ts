export interface ApiResource<T> {
  data: T
}

export interface ApiResourceCollection<T> {
  data: T[]
}

export interface ValidationErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}
