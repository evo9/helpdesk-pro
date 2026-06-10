import { apiClient } from './client'
import type { User, PaginatedResponse } from './types'

export async function getUsers(): Promise<User[]> {
  const { data } = await apiClient.get<PaginatedResponse<User>>('/users')
  return data.member
}

export async function createUser(payload: {
  email: string
  fullName: string
  password: string
  role: string
}): Promise<User> {
  const { data } = await apiClient.post<User>('/users', payload)
  return data
}

export async function updateUser(
  id: string,
  payload: Partial<{ fullName: string; email: string; role: string; isActive: boolean }>
): Promise<User> {
  const { data } = await apiClient.patch<User>(`/users/${id}`, payload)
  return data
}

export async function getMe(): Promise<User> {
  const { data } = await apiClient.get<User>('/users/me')
  return data
}
