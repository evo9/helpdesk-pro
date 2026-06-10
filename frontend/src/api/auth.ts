import { apiClient } from './client'

interface LoginResponse {
  token: string
}

export async function login(email: string, password: string): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/auth/login', { email, password })
  return data
}

export async function refreshToken(): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/auth/refresh')
  return data
}

export async function register(payload: {
  fullName: string
  email: string
  password: string
}): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/auth/register', payload)
  return data
}
