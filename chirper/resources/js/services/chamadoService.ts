import { apiClient } from '../api/client';
import type { CreateChamadoInput, HelpdeskTicket, TicketPriority, TicketStatus } from '../types/helpdesk';

interface RawChamado {
    id: number;
    titulo: string;
    patrimonio: string;
    prioridade: string | null;
    categoria: string | null;
    solicitante: string | null;
    responsavel: string | null;
    tecnico_id?: number | null;
    status: string | null;
    data_abertura?: string | null;
}

export async function fetchChamados(): Promise<HelpdeskTicket[]> {
    const response = await apiClient.get<RawChamado[] | Record<string, RawChamado>>('/api/chamados');
    const rawRows = Array.isArray(response.data) ? response.data : Object.values(response.data ?? {});

    return rawRows
        .slice()
        .sort((a, b) => {
            const aTime = a.data_abertura ? Date.parse(a.data_abertura) : Number.NEGATIVE_INFINITY;
            const bTime = b.data_abertura ? Date.parse(b.data_abertura) : Number.NEGATIVE_INFINITY;

            if (aTime !== bTime) {
                return bTime - aTime;
            }

            return b.id - a.id;
        })
        .map((item) => ({
            id: item.id,
            titulo: item.titulo,
            patrimonio: item.patrimonio,
            prioridade: (item.prioridade ?? 'media') as TicketPriority,
            categoria: item.categoria ?? 'Sem categoria',
            solicitante: item.solicitante ?? 'Desconhecido',
            responsavel: item.responsavel ?? undefined,
            tecnicoId: item.tecnico_id ?? null,
            status: (item.status ?? 'pendente') as TicketStatus,
        }));
}

export async function createChamado(input: CreateChamadoInput): Promise<void> {
    await apiClient.post('/api/chamados', input);
}

export async function assignTechnicianToChamado(input: { id_chamado: number; tecnico_id: number }): Promise<void> {
    await apiClient.post('/api/chamados/atribuir-tecnico', input);
}
