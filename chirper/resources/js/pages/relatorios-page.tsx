import { AnimatePresence, motion } from 'framer-motion';
import {
    ArrowLeft,
    BarChart3,
    CalendarDays,
    CheckCircle2,
    Clock3,
    FileBarChart,
    FileText,
    Loader2,
    Printer,
    Ticket,
    TrendingUp,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

import { Card, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { useAuth } from '../context/auth-context';
import { gerarRelatorioGestaoAdministrativa } from '../services/relatorioService';
import type { RelatorioGestaoAdministrativa } from '../types/helpdesk';

function formatarData(data: string): string {
    if (!data) {
        return '';
    }

    const [ano, mes, dia] = data.split('-');

    return `${dia}/${mes}/${ano}`;
}

function formatarPercentual(valor: number): string {
    return `${valor.toFixed(1)}%`;
}

export function RelatoriosPage() {
    const { user } = useAuth();
    const navigate = useNavigate();

    const [periodoInicial, setPeriodoInicial] = useState('');
    const [periodoFinal, setPeriodoFinal] = useState('');

    const [relatorio, setRelatorio] =
        useState<RelatorioGestaoAdministrativa | null>(null);

    const [carregando, setCarregando] = useState(false);
    const [erro, setErro] = useState<string | null>(null);

    if (!user || user.nivel !== 'adm') {
        return (
            <Card className="border-stone-700/50 bg-stone-900/70">
                <CardContent className="p-8 text-center">
                    <XCircle className="mx-auto mb-4 size-10 text-red-400" />

                    <h2 className="text-xl font-semibold text-stone-100">
                        Acesso restrito
                    </h2>

                    <p className="mt-2 text-sm text-stone-400">
                        Apenas administradores podem acessar os relatórios.
                    </p>
                </CardContent>
            </Card>
        );
    }

    async function handleGerarRelatorio() {
        setErro(null);

        if (!periodoInicial || !periodoFinal) {
            setErro('Informe o período inicial e o período final.');
            return;
        }

        if (periodoInicial > periodoFinal) {
            setErro(
                'O período inicial não pode ser maior que o período final.',
            );
            return;
        }

        try {
            setCarregando(true);

            const resultado =
                await gerarRelatorioGestaoAdministrativa(
                    periodoInicial,
                    periodoFinal,
                );

            setRelatorio(resultado);
        } catch (error) {
            const mensagem =
                error instanceof Error
                    ? error.message
                    : 'Não foi possível gerar o relatório.';

            setErro(mensagem);
            setRelatorio(null);
        } finally {
            setCarregando(false);
        }
    }

    function handleImprimir() {
        window.print();
    }

    return (
        <div className="space-y-5">

            {/* Cabeçalho */}
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div className="flex items-center gap-3">

                        <div className="flex size-11 items-center justify-center rounded-2xl border border-amber-400/20 bg-amber-500/10">
                            <FileBarChart className="size-5 text-amber-300" />
                        </div>

                        <div>
                            <h1 className="text-2xl font-semibold text-stone-100">
                                Relatórios
                            </h1>

                            <p className="text-sm text-stone-400">
                                Gere relatórios administrativos do Help Desk.
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            <AnimatePresence mode="wait">

                {!relatorio ? (

                    <motion.div
                        key="lista"
                        initial={{ opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -12 }}
                        className="space-y-5"
                    >

                        {/* Relatório disponível */}
                        <Card className="glass-panel border-stone-500/20 transition-colors hover:border-amber-400/30">
                            <CardContent className="p-6">

                                <div className="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

                                    <div className="flex gap-4">

                                        <div className="flex size-12 shrink-0 items-center justify-center rounded-2xl border border-amber-400/20 bg-amber-500/10">
                                            <FileBarChart className="size-6 text-amber-300" />
                                        </div>

                                        <div>
                                            <h2 className="font-semibold text-stone-100">
                                                Relatório de Gestão Administrativa do Help Desk
                                            </h2>

                                            <p className="mt-1 max-w-2xl text-sm leading-6 text-stone-400">
                                                Apresenta indicadores gerais dos chamados,
                                                tempos médios e distribuição por categoria.
                                            </p>
                                        </div>

                                    </div>

                                    <Button
                                        type="button"
                                        onClick={() => {
                                            setErro(null);
                                            setRelatorio(null);
                                        }}
                                        className="bg-amber-600 text-white hover:bg-amber-500"
                                    >
                                        <FileText className="mr-2 size-4" />
                                        Gerar relatório
                                    </Button>

                                </div>

                            </CardContent>
                        </Card>

                    </motion.div>

                ) : (

                    <motion.div
                        key="resultado"
                        initial={{ opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        className="space-y-5"
                    >

                        {/* Botões */}
                        <div className="flex flex-wrap items-center justify-between gap-3">

                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setRelatorio(null)}
                                className="border-stone-600/50 bg-stone-900/50 text-stone-200 hover:bg-stone-800"
                            >
                                <ArrowLeft className="mr-2 size-4" />
                                Voltar
                            </Button>

                            <Button
                                type="button"
                                onClick={handleImprimir}
                                className="bg-amber-600 text-white hover:bg-amber-500"
                            >
                                <Printer className="mr-2 size-4" />
                                Imprimir / Salvar PDF
                            </Button>

                        </div>

                        <div
                            id="relatorio-impressao"
                            className="space-y-5"
                        >

                            {/* Cabeçalho do relatório */}
                            <Card className="glass-panel border-stone-500/20">
                                <CardContent className="p-6">

                                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                                        <div>

                                            <div className="flex items-center gap-3">

                                                <div className="flex size-11 items-center justify-center rounded-2xl bg-amber-500/10">
                                                    <BarChart3 className="size-5 text-amber-300" />
                                                </div>

                                                <div>
                                                    <h2 className="text-xl font-semibold text-stone-100">
                                                        Relatório de Gestão
                                                        Administrativa
                                                    </h2>

                                                    <p className="text-sm text-stone-400">
                                                        Help Desk
                                                    </p>
                                                </div>

                                            </div>

                                        </div>

                                        <div className="rounded-xl border border-stone-700/50 bg-stone-950/40 px-4 py-3">

                                            <p className="text-xs text-stone-500">
                                                Período
                                            </p>

                                            <p className="text-sm font-medium text-stone-200">
                                                {formatarData(
                                                    relatorio.periodo.inicio,
                                                )}{' '}
                                                até{' '}
                                                {formatarData(
                                                    relatorio.periodo.fim,
                                                )}
                                            </p>

                                        </div>

                                    </div>

                                </CardContent>
                            </Card>

                            {/* Indicadores */}
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                                <MetricCard
                                    title="Chamados abertos"
                                    value={relatorio.chamados.abertos}
                                    icon={Ticket}
                                />

                                <MetricCard
                                    title="Chamados resolvidos"
                                    value={relatorio.chamados.resolvidos}
                                    icon={CheckCircle2}
                                />

                                <MetricCard
                                    title="Chamados pendentes"
                                    value={relatorio.chamados.pendentes}
                                    icon={Clock3}
                                />

                                <MetricCard
                                    title="Taxa de resolução"
                                    value={formatarPercentual(
                                        relatorio.chamados.taxaResolucao,
                                    )}
                                    icon={TrendingUp}
                                />

                            </div>

                            {/* Tempo médio */}
                            <div className="grid grid-cols-1 gap-4">

                                <Card className="glass-panel border-stone-500/20">
                                    <CardContent className="p-6">

                                        <div className="flex items-center gap-3">

                                            <Clock3 className="size-5 text-amber-300" />

                                            <div>

                                                <p className="text-sm text-stone-400">
                                                    Tempo médio de resolução
                                                </p>

                                                <p className="mt-1 text-2xl font-semibold text-stone-100">
                                                    {relatorio.tempos.medioResolucao}
                                                </p>

                                            </div>

                                        </div>

                                    </CardContent>
                                </Card>

                            </div>

                            {/* Categorias */}
                            <Card className="glass-panel border-stone-500/20">
                                <CardContent className="p-6">

                                    <div className="mb-5 flex items-center gap-3">

                                        <BarChart3 className="size-5 text-amber-300" />

                                        <div>
                                            <h3 className="font-semibold text-stone-100">
                                                Chamados por categoria
                                            </h3>

                                            <p className="text-sm text-stone-400">
                                                Distribuição dos chamados no
                                                período selecionado.
                                            </p>
                                        </div>

                                    </div>

                                    <div className="space-y-4">

                                        {relatorio.categorias.map(
                                            (categoria) => (
                                                <div
                                                    key={categoria.categoria}
                                                    className="space-y-2"
                                                >

                                                    <div className="flex justify-between text-sm">

                                                        <span className="text-stone-300">
                                                            {categoria.categoria}
                                                        </span>

                                                        <span className="text-stone-400">
                                                            {categoria.quantidade}{' '}
                                                            chamados (
                                                            {formatarPercentual(
                                                                categoria.percentual,
                                                            )}
                                                            )
                                                        </span>

                                                    </div>

                                                    <div className="h-2 overflow-hidden rounded-full bg-stone-800">

                                                        <motion.div
                                                            initial={{
                                                                width: 0,
                                                            }}
                                                            animate={{
                                                                width: `${Math.min(
                                                                    categoria.percentual,
                                                                    100,
                                                                )}%`,
                                                            }}
                                                            transition={{
                                                                duration: 0.7,
                                                            }}
                                                            className="h-full rounded-full bg-amber-500"
                                                        />

                                                    </div>

                                                </div>
                                            ),
                                        )}

                                        {relatorio.categorias.length === 0 && (
                                            <p className="py-6 text-center text-sm text-stone-500">
                                                Nenhum chamado encontrado por
                                                categoria.
                                            </p>
                                        )}

                                    </div>

                                </CardContent>
                            </Card>

                        </div>

                    </motion.div>
                )}

            </AnimatePresence>

            {/* Período */}
            {!relatorio && (
                <Card className="glass-panel border-stone-500/20">
                    <CardContent className="p-6">

                        <div className="mb-5 flex items-center gap-3">

                            <CalendarDays className="size-5 text-amber-300" />

                            <div>
                                <h2 className="font-semibold text-stone-100">
                                    Período do relatório
                                </h2>

                                <p className="text-sm text-stone-400">
                                    Selecione o intervalo que será analisado.
                                </p>
                            </div>

                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">

                            <div>
                                <label className="mb-2 block text-sm text-stone-300">
                                    Data inicial
                                </label>

                                <Input
                                    type="date"
                                    value={periodoInicial}
                                    onChange={(event) =>
                                        setPeriodoInicial(event.target.value)
                                    }
                                    className="border-stone-600/40 bg-stone-950/40 text-stone-100"
                                />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm text-stone-300">
                                    Data final
                                </label>

                                <Input
                                    type="date"
                                    value={periodoFinal}
                                    onChange={(event) =>
                                        setPeriodoFinal(event.target.value)
                                    }
                                    className="border-stone-600/40 bg-stone-950/40 text-stone-100"
                                />
                            </div>

                        </div>

                        {erro && (
                            <div className="mt-4 rounded-xl border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-300">
                                {erro}
                            </div>
                        )}

                        <div className="mt-5 flex justify-end">

                            <Button
                                type="button"
                                onClick={handleGerarRelatorio}
                                disabled={carregando}
                                className="bg-amber-600 text-white hover:bg-amber-500 disabled:opacity-60"
                            >

                                {carregando ? (
                                    <>
                                        <Loader2 className="mr-2 size-4 animate-spin" />
                                        Gerando...
                                    </>
                                ) : (
                                    <>
                                        <FileBarChart className="mr-2 size-4" />
                                        Gerar relatório
                                    </>
                                )}

                            </Button>

                        </div>

                    </CardContent>
                </Card>
            )}

        </div>
    );
}

interface MetricCardProps {
    title: string;
    value: string | number;
    icon: React.ComponentType<{ className?: string }>;
}

function MetricCard({
    title,
    value,
    icon: Icon,
}: MetricCardProps) {
    return (
        <Card className="glass-panel border-stone-500/20">
            <CardContent className="p-5">

                <div className="flex items-center justify-between">

                    <div>

                        <p className="text-sm text-stone-400">
                            {title}
                        </p>

                        <p className="mt-2 text-3xl font-semibold text-stone-100">
                            {value}
                        </p>

                    </div>

                    <div className="flex size-11 items-center justify-center rounded-2xl border border-amber-400/20 bg-amber-500/10">
                        <Icon className="size-5 text-amber-300" />
                    </div>

                </div>

            </CardContent>
        </Card>
    );
}