import { useEffect, useState } from 'react';

import {
    fetchHistoryByTicketId,
    type History
} from '../services/historyService';

interface UseHistoryResult {
    historicos: History[];
    isLoading: boolean;
    error: string | null;
    reloadHistory: () => void;
}

export function useHistory(idChamado: number | null): UseHistoryResult {
    const [historicos, setHistoricos] = useState<History[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [refreshIndex, setRefreshIndex] = useState(0);

    useEffect(() => {
        if (!idChamado) {
            setHistoricos([]);
            setIsLoading(false);
            return;
        }

        let cancelled = false;

        setIsLoading(true);
        setError(null);

        fetchHistoryByTicketId(idChamado)
            .then((data) => {
                if (!cancelled) {
                    setHistoricos(data);
                }
            })
            .catch((err: unknown) => {
                if (!cancelled) {
                    const message =
                        err instanceof Error
                            ? err.message
                            : 'Erro ao carregar histórico';

                    setError(message);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setIsLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [idChamado, refreshIndex]);

    return {
        historicos,
        isLoading,
        error,
        reloadHistory: () => setRefreshIndex((value) => value + 1),
    };
}