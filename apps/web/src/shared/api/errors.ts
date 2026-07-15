import axios from 'axios'
import type { ValidationErrorResponse } from '@/shared/types/api'

export interface ParsedValidationError {
  message: string
  errors: string[]
}

export function getValidationError(
  exception: unknown,
  fallbackMessage = 'Проверьте введённые данные.',
): ParsedValidationError | null {
  if (!axios.isAxiosError<ValidationErrorResponse>(exception)) {
    return null
  }

  if (exception.response?.status !== 422) {
    return null
  }

  const data = exception.response.data

  return {
    message: data.message || fallbackMessage,
    errors: Object.values(data.errors ?? {}).flat(),
  }
}
