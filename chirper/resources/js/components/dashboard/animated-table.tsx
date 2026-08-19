import { motion } from 'framer-motion';
import { useState, type ChangeEvent } from 'react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { HelpdeskTicket, HelpdeskUser, UserRole } from '@/types/helpdesk';

function priorityVariant(priority: HelpdeskTicket['prioridade']) {
    if (priority === 'muito alta' || priority === 'alta') {
        return 'danger';
    }

    if (priority === 'media') {
        return 'warning';
    }

    return 'default';
}

interface AnimatedTableProps {
    rows: HelpdeskTicket[];
    canAssignTechnicians?: boolean;
    technicians?: HelpdeskUser[];
    isAssigningTicketId?: number | null;
    assignmentFeedback?: string | null;
    assignmentError?: string | null;
    techniciansLoading?: boolean;
    onTicketClick?: (ticket: HelpdeskTicket) => void;
    technicianLoadError?: string | null;
    onAssignTechnician?: (ticketId: number, technicianId: number) => void;
    userRole?: UserRole;
    onUpdateStatus?: (ticketId: number, newStatus: string) => void;
    isUpdatingStatusId?: number | null;
}

export function AnimatedTable({
    rows,
    canAssignTechnicians = false,
    technicians = [],
    isAssigningTicketId = null,
    assignmentFeedback = null,
    assignmentError = null,
    techniciansLoading = false,
    technicianLoadError = null,
    onAssignTechnician,
    userRole,
    isUpdatingStatusId = null,
    onUpdateStatus,
    onTicketClick,
}: AnimatedTableProps) {
    const showAssignmentColumn =
        canAssignTechnicians &&
        (userRole === 'analista' || userRole === 'adm');

    const [statusLocal, setStatusLocal] =
        useState<Record<number, string>>({});

    function handleTechnicianChange(
        ticketId: number,
        event: ChangeEvent<HTMLSelectElement>
    ) {
        const technicianId = Number(event.target.value);

        if (
            Number.isNaN(technicianId) ||
            technicianId <= 0 ||
            !onAssignTechnician
        ) {
            return;
        }

        onAssignTechnician(ticketId, technicianId);
    }

    function handleStatusChange(
        ticketId: number,
        event: ChangeEvent<HTMLSelectElement>
    ) {
        const newStatus = event.target.value;
        const selectElement = event.target;

        selectElement.blur();

        setStatusLocal((prev) => ({
            ...prev,
            [ticketId]: newStatus,
        }));

        if (newStatus === 'pendente') {
            setTimeout(
                () => alert(`Chamado #${ticketId} marcado como Pendente!`),
                0
            );
        }

        if (newStatus === 'concluido') {
            setTimeout(
                () => alert(`Chamado #${ticketId} marcado como Concluído!`),
                0
            );
        }

        if (newStatus === 'cancelado') {
            setTimeout(
                () => alert(`Chamado #${ticketId} marcado como Cancelado!`),
                0
            );
        }

        onUpdateStatus?.(ticketId, newStatus);
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Chamados recentes</CardTitle>
            </CardHeader>

            <CardContent>
                {assignmentFeedback ? (
                    <div className="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {assignmentFeedback}
                    </div>
                ) : null}

                {assignmentError ? (
                    <div className="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {assignmentError}
                    </div>
                ) : null}

                {technicianLoadError ? (
                    <div className="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {technicianLoadError}
                    </div>
                ) : null}

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-xs uppercase tracking-wide text-stone-400">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    Título
                                </th>

                                <th className="px-3 py-2 font-medium">
                                    Categoria
                                </th>

                                <th className="px-3 py-2 font-medium">
                                    Prioridade
                                </th>

                                <th className="px-3 py-2 font-medium">
                                    Status
                                </th>

                                <th className="px-3 py-2 font-medium">
                                    Responsável
                                </th>

                                {showAssignmentColumn ? (
                                    <th className="px-3 py-2 font-medium">
                                        Atribuir técnico
                                    </th>
                                ) : null}
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-stone-700/60">
                            {rows.map((row, index) => (
                                <motion.tr
                                    key={row.id}
                                    initial={{ opacity: 0, y: 10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{
                                        delay: 0.2 + index * 0.07,
                                    }}
                                    onClick={() => onTicketClick?.(row)}
                                    className="cursor-pointer hover:bg-stone-800/45"
                                >
                                    <td className="px-3 py-3 text-stone-100">
                                        {row.titulo}
                                    </td>

                                    <td className="px-3 py-3 text-stone-300">
                                        {row.categoria}
                                    </td>

                                    <td className="px-3 py-3">
                                        <Badge
                                            variant={priorityVariant(
                                                row.prioridade
                                            )}
                                        >
                                            {row.prioridade}
                                        </Badge>
                                    </td>

                                    <td
                                        className="px-3 py-3"
                                        onClick={(event) =>
                                            event.stopPropagation()
                                        }
                                    >
                                        <select
                                            value={
                                                statusLocal[row.id] ||
                                                row.status
                                            }
                                            disabled={
                                                isUpdatingStatusId ===
                                                    row.id ||
                                                (userRole !== 'tecnico' &&
                                                    userRole !== 'analista')
                                            }
                                            onChange={(event) =>
                                                handleStatusChange(
                                                    row.id,
                                                    event
                                                )
                                            }
                                            className="w-full min-w-36 rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 capitalize disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <option value="pendente">
                                                Pendente
                                            </option>

                                            {(userRole === 'tecnico' ||
                                                userRole === 'analista') && (
                                                <>
                                                    <option value="concluido">
                                                        Concluído
                                                    </option>

                                                    <option value="cancelado">
                                                        Cancelado
                                                    </option>
                                                </>
                                            )}
                                        </select>

                                        {isUpdatingStatusId === row.id ? (
                                            <p className="mt-1 text-xs text-amber-200">
                                                Atualizando...
                                            </p>
                                        ) : null}
                                    </td>

                                    <td className="px-3 py-3 text-stone-300">
                                        {row.responsavel ?? 'A definir'}
                                    </td>

                                    {showAssignmentColumn ? (
                                        <td
                                            className="px-3 py-3"
                                            onClick={(event) =>
                                                event.stopPropagation()
                                            }
                                        >
                                            <select
                                                value={row.tecnicoId ?? ''}
                                                disabled={
                                                    isAssigningTicketId ===
                                                        row.id ||
                                                    techniciansLoading ||
                                                    technicians.length === 0
                                                }
                                                onChange={(event) =>
                                                    handleTechnicianChange(
                                                        row.id,
                                                        event
                                                    )
                                                }
                                                className="w-full min-w-44 rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                <option value="">
                                                    {techniciansLoading
                                                        ? 'Carregando técnicos...'
                                                        : 'Selecionar técnico'}
                                                </option>

                                                {technicians.map(
                                                    (technician) => (
                                                        <option
                                                            key={technician.id}
                                                            value={
                                                                technician.id
                                                            }
                                                        >
                                                            {technician.nome}
                                                        </option>
                                                    )
                                                )}
                                            </select>

                                            {isAssigningTicketId === row.id ? (
                                                <p className="mt-1 text-xs text-amber-200">
                                                    Atribuindo...
                                                </p>
                                            ) : null}

                                            {!techniciansLoading &&
                                            technicians.length === 0 ? (
                                                <p className="mt-1 text-xs text-stone-500">
                                                    Nenhum técnico disponível
                                                </p>
                                            ) : null}
                                        </td>
                                    ) : null}
                                </motion.tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}