import { apiClient } from '../api/client';
import type { HelpdeskCategory } from '../types/helpdesk';

export async function fetchCategorias(): Promise<HelpdeskCategory[]> {
    const response = await apiClient.get<HelpdeskCategory[]>('/api/categorias');
    return response.data;
}