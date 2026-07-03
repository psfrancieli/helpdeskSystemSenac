import { apiClient } from '../api/client';
import type { HelpdeskTicket, TicketPriority, TicketStatus } from '../types/helpdesk';

interface RawChamado {
    id: number;
    titulo: string;
    patrimonio: string;
    prioridade: string;
    categoria: string | null;
    solicitante: string | null;
    responsavel: string | null;
    status: string | null;
}

export async function fetchChamados(): Promise<HelpdeskTicket[]> {
    const response = await apiClient.get<RawChamado[]>('/api/chamados');

    return response.data.map((item) => ({
        id: item.id,
        titulo: item.titulo,
        patrimonio: item.patrimonio,
        prioridade: item.prioridade as TicketPriority,
        categoria: item.categoria ?? 'Sem categoria',
        solicitante: item.solicitante ?? 'Desconhecido',
        responsavel: item.responsavel ?? undefined,
        status: (item.status ?? 'pendente') as TicketStatus,
    }));
}
