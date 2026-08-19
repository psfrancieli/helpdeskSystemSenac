import { useEffect, useState } from 'react';

import { fetchCategorias } from '../services/categoriaService';
import type { HelpdeskCategory } from '../types/helpdesk';

interface UseCategoriasResult {
    categorias: HelpdeskCategory[];
    isLoading: boolean;
    error: string | null;
    reloadCategorias: () => void;
}

export function useCategorias(): UseCategoriasResult {
    const [categorias, setCategorias] = useState<HelpdeskCategory[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [refreshIndex, setRefreshIndex] = useState(0);

    useEffect(() => {
        let cancelled = false;

        setIsLoading(true);
        setError(null);

        fetchCategorias()
            .then((data) => {
                if (!cancelled) {
                    setCategorias(data);
                }
            })
            .catch((err: unknown) => {
                if (!cancelled) {
                    const message = err instanceof Error ? err.message : 'Erro ao carregar categorias';
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
    }, [refreshIndex]);

    return {
        categorias,
        isLoading,
        error,
        reloadCategorias: () => setRefreshIndex((value) => value + 1),
    };
}