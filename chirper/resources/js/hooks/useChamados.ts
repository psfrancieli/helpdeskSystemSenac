import { useTickets } from './useTickets';
import type { HelpdeskTicket } from '../types/helpdesk';

interface UseChamadosResult {
    chamados: HelpdeskTicket[];
    isLoading: boolean;
    error: string | null;
    refreshChamados: () => Promise<void>;
}

export function useChamados(): UseChamadosResult {
    const { tickets, isLoading, error, refreshTickets } = useTickets();

    return {
        chamados: tickets,
        isLoading,
        error,
        refreshChamados: refreshTickets,
    };
}
