import { apiClient } from '../api/client';
import type { RelatorioGestaoAdministrativa } from '../types/helpdesk';

export async function gerarRelatorioGestaoAdministrativa(
    periodoInicial: string,
    periodoFinal: string,
): Promise<RelatorioGestaoAdministrativa> {
    const params = new URLSearchParams({
        periodo_inicial: periodoInicial,
        periodo_final: periodoFinal,
    });

    const response = await apiClient.get<RelatorioGestaoAdministrativa>(
        `/api/relatorios/gestao-administrativa?${params.toString()}`,
    );

    return response.data;
}