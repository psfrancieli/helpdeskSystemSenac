import { useEffect, useState } from 'react';

import { fetchUsuarios } from '../services/usuarioService';
import type { HelpdeskUser } from '../types/helpdesk';

interface UseUsuariosResult {
    usuarios: HelpdeskUser[];
    isLoading: boolean;
    error: string | null;
    reloadUsuarios: () => void;
}

export function useUsuarios(): UseUsuariosResult {
    const [usuarios, setUsuarios] = useState<HelpdeskUser[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [refreshIndex, setRefreshIndex] = useState(0);

    useEffect(() => {
        let cancelled = false;

        setIsLoading(true);
        setError(null);

        fetchUsuarios()
            .then((data) => {
                if (!cancelled) {
                    setUsuarios(data);
                }
            })
            .catch((err: unknown) => {
                if (!cancelled) {
                    const message = err instanceof Error ? err.message : 'Erro ao carregar usuários';
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
        usuarios,
        isLoading,
        error,
        reloadUsuarios: () => setRefreshIndex((value) => value + 1),
    };
}