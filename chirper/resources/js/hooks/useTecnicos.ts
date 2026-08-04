import { useEffect, useState } from 'react';

import { fetchTecnicos } from '../services/tecnicoService';
import type { HelpdeskUser } from '../types/helpdesk';

interface UseTecnicosResult {
    tecnicos: HelpdeskUser[];
    isLoading: boolean;
    error: string | null;
    reloadTecnicos: () => void;
}

export function useTecnicos(): UseTecnicosResult {
    const [tecnicos, setTecnicos] = useState<HelpdeskUser[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [refreshIndex, setRefreshIndex] = useState(0);

    useEffect(() => {
        let cancelled = false;

        setIsLoading(true);
        setError(null);

        fetchTecnicos()
            .then((data) => {
                if (!cancelled) {
                    setTecnicos(data);
                }
            })
            .catch((err: unknown) => {
                if (!cancelled) {
                    const message = err instanceof Error ? err.message : 'Erro ao carregar técnicos';
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
        tecnicos,
        isLoading,
        error,
        reloadTecnicos: () => setRefreshIndex((value) => value + 1),
    };
}

// Assinatura incompatível da action
// A rota em web.php:233 usa padrão [Classe, método], e o Router executa sem argumentos em Router.php:32.
// O método atual de criação em CalledController.php:31 é store(): void (sem parâmetros).
// Já o equivalente em TicketController.php:56 é criar(array $dadosRequisicao): array.
// Se trocar direto, o PHP vai acusar falta de argumento (Too few arguments).