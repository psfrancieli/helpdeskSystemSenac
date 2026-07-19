import { useEffect, useState } from 'react';

import { fetchChamados } from '../services/chamadoService';
import type { HelpdeskTicket } from '../types/helpdesk';

interface UseChamadosResult {
    chamados: HelpdeskTicket[];
    isLoading: boolean;
    error: string | null;
    reloadChamados: () => void;
}

export function useChamados(): UseChamadosResult {
    const [chamados, setChamados] = useState<HelpdeskTicket[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [refreshIndex, setRefreshIndex] = useState(0);

    useEffect(() => {
        let cancelled = false;

        setIsLoading(true);
        setError(null);

        fetchChamados()
            .then((data) => {
                if (!cancelled) setChamados(data);
            })
            .catch((err: unknown) => {
                if (!cancelled) {
                    const message = err instanceof Error ? err.message : 'Erro ao carregar chamados';
                    setError(message);
                }
            })
            .finally(() => {
                if (!cancelled) setIsLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [refreshIndex]);

    return { chamados, isLoading, error, reloadChamados: () => setRefreshIndex((value) => value + 1) };
}
