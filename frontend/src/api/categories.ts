import { apiClient } from './client'
import type { Category, PaginatedResponse } from './types'

export async function getCategories(): Promise<PaginatedResponse<Category>> {
  const { data } = await apiClient.get<PaginatedResponse<Category>>('/categories')
  return data
}

export async function createCategory(payload: { name: string }): Promise<Category> {
  const { data } = await apiClient.post<Category>('/categories', payload)
  return data
}

export async function updateCategory(id: string, payload: { name: string }): Promise<Category> {
  const { data } = await apiClient.patch<Category>(`/categories/${id}`, payload)
  return data
}

export async function deleteCategory(id: string): Promise<void> {
  await apiClient.delete(`/categories/${id}`)
}
