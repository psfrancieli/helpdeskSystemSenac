import { apiClient } from '../api/client';
import type { CreateApiUserInput } from '../types/helpdesk';

export async function createUsuario(input: CreateApiUserInput): Promise<void> {
    await apiClient.post('/api/usuarios', input);
}

export async function updateMeuTelefone(telefone: string): Promise<{ telefone: string }> {
    const response = await apiClient.post<{ telefone: string }>('/api/me', { telefone });
    return response.data;
}