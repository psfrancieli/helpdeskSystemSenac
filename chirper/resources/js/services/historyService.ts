import { apiClient } from '../api/client';

export interface History {
    data: string;
    descricao: string;
    id_chamado: number;
    id_usuario_tecnico: number;
}

interface HistoryResponse {
    success: boolean;
    data: History[];
    message?: string;
}

export async function fetchHistoryByTicketId(
    idChamado: number
): Promise<History[]> {
    const response = await apiClient.get<HistoryResponse>(
        `/api/historico?id_chamado=${idChamado}`
    );

    return response.data.data;
}