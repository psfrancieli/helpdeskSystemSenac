import { apiClient } from '../api/client';
import type { CreateApiUserInput } from '../types/helpdesk';

export async function createUsuario(input: CreateApiUserInput): Promise<void> {
    await apiClient.post('/api/usuarios', input);
}
