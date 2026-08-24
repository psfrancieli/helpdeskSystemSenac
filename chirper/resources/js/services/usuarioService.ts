import { apiClient } from '../api/client';
import type { CreateApiUserInput, HelpdeskUser, UserRole } from '../types/helpdesk';

export async function createUsuario(input: CreateApiUserInput): Promise<void> {
    await apiClient.post('/api/usuarios', input);
}

export async function updateMeuTelefone(telefone: string): Promise<{ telefone: string }> {
    const response = await apiClient.post<{ telefone: string }>('/api/me', { telefone });
    return response.data;
}

interface RawUsuario {
    id: number;
    nome: string;
    email: string;
    nivel: UserRole;
    ativo: boolean;
    telefone?: string;
}

export async function fetchUsuarios(): Promise<HelpdeskUser[]> {
    const response = await apiClient.get<RawUsuario[]>('/api/usuarios');

    return response.data.map((usuario) => ({
        id: usuario.id,
        nome: usuario.nome,
        email: usuario.email,
        nivel: usuario.nivel,
        ativo: usuario.ativo,
        telefone: usuario.telefone ?? '',
    }));
}

export async function alterarNivelUsuario(id: number, nivel: UserRole): Promise<void> {
    await apiClient.post('/api/usuarios/alterar-nivel', { id, nivel });
}

export async function resetarSenhaUsuario(id: number): Promise<void> {
    await apiClient.post('/api/usuarios/resetar-senha', { id });
}