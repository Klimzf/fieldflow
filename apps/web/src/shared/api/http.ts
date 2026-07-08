import axios from 'axios'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
  },
})

export async function csrf(): Promise<void> {
  await http.get('/sanctum/csrf-cookie')
}
