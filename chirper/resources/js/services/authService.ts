import { apiClient } from '../api/client';
import type { HelpdeskUser } from '../types/helpdesk';

interface LoginPayload {
    email: string;
    senha: string;
}

export async function loginRequest(credentials: LoginPayload): Promise<HelpdeskUser> {
    const response = await apiClient.post<HelpdeskUser>('/api/login', credentials);
    return response.data;
}

export async function fetchAuthenticatedUser(): Promise<HelpdeskUser> {
    const response = await apiClient.get<HelpdeskUser>('/api/me');
    return response.data;
}

export async function logoutRequest(): Promise<void> {
    await apiClient.post('/api/logout', {});
}
