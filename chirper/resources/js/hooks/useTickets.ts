import { useEffect, useState } from 'react';

import { fetchTickets } from '../services/ticketService';
import type { HelpdeskTicket } from '../types/helpdesk';

interface UseTicketsResult {
    tickets: HelpdeskTicket[];
    isLoading: boolean;
    error: string | null;
    refreshTickets: () => Promise<void>;
}

export function useTickets(): UseTicketsResult {
    const [tickets, setTickets] = useState<HelpdeskTicket[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    async function refreshTickets(): Promise<void> {
        setIsLoading(true);
        setError(null);

        try {
            const data = await fetchTickets();
            setTickets(data);
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Erro ao carregar chamados';
            setError(message);
        } finally {
            setIsLoading(false);
        }
    }

    useEffect(() => {
        refreshTickets().catch(() => {
            return;
        });
    }, []);

    return { tickets, isLoading, error, refreshTickets };
}
