import { apiClient } from '../api/client';
import type { CreateHelpdeskUser, HelpdeskUser, UserRole } from '../types/helpdesk';

export async function fetchUsuarios(): Promise<HelpdeskUser[]> {
    const response = await apiClient.get<HelpdeskUser[]>('/api/usuarios');
    return response.data;
}

export async function createUsuario(payload: CreateHelpdeskUser): Promise<{
    message: string;
    data: HelpdeskUser;
}> {
    const response = await apiClient.post<HelpdeskUser>('/api/criarUsuario', payload);
    return {
        message: 'Usuário criado com sucesso',
        data: response.data,
    };
}

export async function refreshUsuarios(): Promise<HelpdeskUser[]> {
    const response = await apiClient.get<HelpdeskUser[]>('/api/usuarios');
    return response.data;
}