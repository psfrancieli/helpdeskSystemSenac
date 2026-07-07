import { apiClient } from '../api/client';
import type {
    CreateHelpdeskTicket,
    HelpdeskTicket,
    TicketPriority,
    TicketStatus,
} from '../types/helpdesk';

interface RawTicket {
    id: number;
    titulo: string;
    patrimonio: string;
    prioridade: string;
    categoria: string | null;
    solicitante: string | null;
    responsavel: string | null;
    status: string | null;
}

interface RawCreatedTicket {
    id: number;
    uuid: string;
    titulo: string;
    descricao: string;
    prioridade: string;
    status: string;
    patrimonio: string;
    id_categoria: number;
    id_usuario: number;
    id_responsavel: number | null;
}

export async function fetchTickets(): Promise<HelpdeskTicket[]> {
    const response = await apiClient.get<RawTicket[]>('/api/chamados');

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

export async function createTicket(payload: CreateHelpdeskTicket): Promise<{
    message: string;
    data: RawCreatedTicket;
}> {
    const response = await apiClient.post<RawCreatedTicket>('/api/chamados', payload);

    return {
        message: response.message ?? 'Chamado criado com sucesso',
        data: response.data,
    };
}
