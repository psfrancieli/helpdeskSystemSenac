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
            
            .then((data: any) => {
                if (!cancelled && Array.isArray(data)) {
                    
                    const chamadosFormatados: HelpdeskTicket[] = data.map((item) => ({
                        ...item,
                        id: Number(item.id || item.id_chamado),
                        
                        tecnicoId: item.tecnico_id ? Number(item.tecnico_id) : (item.id_responsavel ? Number(item.id_responsavel) : null),
                        
                        categoria: item.categoria || item.nome_categoria || (item.id_categoria ? `Cat. ID ${item.id_categoria}` : 'Sem categoria'),
                        
                        responsavel: item.responsavel || item.nome_responsavel || (item.id_responsavel ? `Técnico ID ${item.id_responsavel}` : 'A definir'),
                    }));

                    setChamados(chamadosFormatados);
                }
            })
            .catch((err: unknown) => {
                if (!cancelled) {
                    const message = err instanceof Error ? err.message : 'Erro ao carregar chamados';
                    setError(message);
                    setTimeout(() => {
                        if (!cancelled) {
                            setError(null); 
                        }
                    }, 3500);
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