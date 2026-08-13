import { apiClient } from '../api/client';
import type { HelpdeskUser } from '../types/helpdesk';

interface RawTecnico {
    id: number;
    nome: string;
    email: string;
    nivel: string;
    ativo: boolean;
    telefone?: string;
}

export async function fetchTecnicos(): Promise<HelpdeskUser[]> {
    const response = await apiClient.get<RawTecnico[]>('/api/tecnicos');

    return response.data.map((tecnico) => ({
        id: tecnico.id,
        nome: tecnico.nome,
        email: tecnico.email,
        nivel: 'tecnico',
        ativo: tecnico.ativo,
        telefone: tecnico.telefone ?? '',
    }));
}
