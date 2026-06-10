import { apiClient } from './client'
import type { Category, PaginatedResponse } from './types'

export async function getCategories(): Promise<Category[]> {
  const { data } = await apiClient.get<PaginatedResponse<Category>>('/categories')
  return data.member
}

export async function createCategory(payload: {
  name: string
  description?: string
}): Promise<Category> {
  const { data } = await apiClient.post<Category>('/categories', payload)
  return data
}

export async function updateCategory(
  id: string,
  payload: Partial<{ name: string; description: string; isActive: boolean }>
): Promise<Category> {
  const { data } = await apiClient.patch<Category>(`/categories/${id}`, payload)
  return data
}

export async function deleteCategory(id: string): Promise<void> {
  await apiClient.delete(`/categories/${id}`)
}
