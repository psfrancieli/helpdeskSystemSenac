import { apiClient } from '../api/client';

import type { RelatorioGestaoAdministrativa } from '../types/helpdesk';

interface MetricasPeriodo {
    resolvidos: number;
    pendentes: number;
    cancelados: number;
}

interface DashboardMetrica {
    chamados_abertos: number;
    chamados_resolvidos: number;
    chamados_pendentes: number;
    taxa_resolucao: string;
    tempo_medio_resolucao: string;
}

interface MetricaCategoria {
    categoria: string;
    quantidade: number;
    porcentagem: string;
}

export async function gerarRelatorioGestaoAdministrativa(
    periodoInicial: string,
    periodoFinal: string,
): Promise<RelatorioGestaoAdministrativa> {

    const body = {
        data_inicio: periodoInicial,
        data_fim: periodoFinal,
    };

    const [
        metricasResponse,
        dashboardResponse,
        categoriaResponse,
    ] = await Promise.all([
        apiClient.post<MetricasPeriodo>(
            '/api/gerarRelatorio/metricasPeriodo',
            body,
        ),

        apiClient.post<DashboardMetrica>(
            '/api/gerarRelatorio/dashboardMetrica',
            body,
        ),

        apiClient.post<MetricaCategoria[]>(
            '/api/gerarRelatorio/metricaCategoria',
            body,
        ),
    ]);

    const metricas = metricasResponse.data;
    const dashboard = dashboardResponse.data;
    const categorias = categoriaResponse.data;

    return {
        periodo: {
            inicio: periodoInicial,
            fim: periodoFinal,
        },

        chamados: {
            abertos: dashboard.chamados_abertos,
            resolvidos: dashboard.chamados_resolvidos,
            pendentes: dashboard.chamados_pendentes,
            taxaResolucao: Number(
                dashboard.taxa_resolucao.replace('%', ''),
            ),
        },

        tempos: {
            medioResolucao: dashboard.tempo_medio_resolucao,
        },

        categorias: categorias.map((item) => ({
            categoria: item.categoria,
            quantidade: item.quantidade,
            percentual: Number(item.porcentagem),
        })),
    };
}