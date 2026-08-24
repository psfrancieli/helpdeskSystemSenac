import { useCallback, useEffect, useRef, useState } from 'react';

import { fetchChamados } from '../services/chamadoService';
import type { HelpdeskNotification, HelpdeskTicket, HelpdeskUser, NotificationType } from '../types/helpdesk';

const POLL_INTERVAL_MS = 20000;
const MAX_NOTIFICATIONS = 40;
const FIRST_RUN_SEED_LIMIT = 20;

interface TicketSnapshot {
    status: string;
    responsavel: string | null;
    prioridade: string;
}

type SnapshotMap = Record<number, TicketSnapshot>;

function feedKey(userId: number): string {
    return `helpdesk.notifications.feed.${userId}`;
}

function snapshotKey(userId: number): string {
    return `helpdesk.notifications.snapshot.${userId}`;
}

function loadFeed(userId: number): HelpdeskNotification[] {
    try {
        const raw = localStorage.getItem(feedKey(userId));
        return raw ? (JSON.parse(raw) as HelpdeskNotification[]) : [];
    } catch {
        return [];
    }
}

function saveFeed(userId: number, feed: HelpdeskNotification[]): void {
    try {
        localStorage.setItem(feedKey(userId), JSON.stringify(feed.slice(0, MAX_NOTIFICATIONS)));
    } catch {
        // localStorage indisponível (modo privado, cota excedida, etc.) — segue sem persistir.
    }
}

function loadSnapshot(userId: number): SnapshotMap | null {
    try {
        const raw = localStorage.getItem(snapshotKey(userId));
        return raw ? (JSON.parse(raw) as SnapshotMap) : null;
    } catch {
        return null;
    }
}

function saveSnapshot(userId: number, snapshot: SnapshotMap): void {
    try {
        localStorage.setItem(snapshotKey(userId), JSON.stringify(snapshot));
    } catch {
        // ignora falha de persistência
    }
}

function sameName(a?: string | null, b?: string | null): boolean {
    if (!a || !b) return false;
    return a.trim().toLocaleLowerCase('pt-BR') === b.trim().toLocaleLowerCase('pt-BR');
}

function ticketNumber(id: number): string {
    return String(id).padStart(4, '0');
}

interface UseNotificationsResult {
    notifications: HelpdeskNotification[];
    unreadCount: number;
    isLoading: boolean;
    error: string | null;
    markAsRead: (id: string) => void;
    markAllAsRead: () => void;
    deleteNotifications: (ids: string[]) => void;
    deleteAllNotifications: () => void;
    refresh: () => void;
}
export function useNotifications(user: HelpdeskUser | null): UseNotificationsResult {
    const [notifications, setNotifications] = useState<HelpdeskNotification[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const userRef = useRef(user);
    userRef.current = user;

    const evaluate = useCallback((tickets: HelpdeskTicket[]) => {
        const currentUser = userRef.current;
        if (!currentUser) return;

        const previousSnapshot = loadSnapshot(currentUser.id);
        const isFirstRun = previousSnapshot === null;
        const nextSnapshot: SnapshotMap = {};
        const generated: HelpdeskNotification[] = [];
        const isStaff = currentUser.nivel === 'analista' || currentUser.nivel === 'adm';
        const nowIso = new Date().toISOString();
        let firstRunSeeded = 0;

        const push = (type: NotificationType, title: string, detail: string, ticketId: number) => {
            generated.push({
                id: `${ticketId}-${type}-${nowIso}-${Math.random().toString(36).slice(2, 8)}`,
                type,
                title,
                detail,
                ticketId,
                timestamp: nowIso,
                read: isFirstRun,
            });
        };

        for (const ticket of tickets) {
            const previous = previousSnapshot?.[ticket.id];
            const responsavelAtual = ticket.responsavel ?? null;

            nextSnapshot[ticket.id] = {
                status: ticket.status,
                responsavel: responsavelAtual,
                prioridade: ticket.prioridade,
            };

            const souSolicitante = sameName(ticket.solicitante, currentUser.nome);
            const souResponsavel = sameName(responsavelAtual, currentUser.nome);
            const numero = ticketNumber(ticket.id);

            if (!previous) {
                // Chamado que o front ainda não conhecia: em uso normal é um
                // chamado novo; na primeira carga do usuário, é o histórico
                // existente, usado para popular o painel (marcado como lido).
                if (isFirstRun && firstRunSeeded >= FIRST_RUN_SEED_LIMIT) {
                    continue;
                }

                let seeded = false;

                if (isStaff) {
                    push('novo', `Novo chamado #${numero}`, `${ticket.solicitante} abriu um novo chamado.`, ticket.id);
                    seeded = true;
                }

                if (responsavelAtual && souResponsavel) {
                    push(
                        'atribuido',
                        `Chamado #${numero} atribuído a você`,
                        'Você foi designado como responsável por este chamado.',
                        ticket.id,
                    );
                    seeded = true;
                }

                if (ticket.status === 'concluido' && (souSolicitante || souResponsavel || isStaff)) {
                    push('resolvido', `Chamado #${numero} resolvido`, 'O chamado foi marcado como resolvido.', ticket.id);
                    seeded = true;
                }

                if (ticket.status === 'cancelado' && (souSolicitante || isStaff)) {
                    push('cancelado', `Chamado #${numero} cancelado`, 'A solicitação foi cancelada.', ticket.id);
                    seeded = true;
                }

                if (isFirstRun && seeded) {
                    firstRunSeeded += 1;
                }

                continue;
            }

            // Técnico designado agora (não tinha responsável antes).
            if (!previous.responsavel && responsavelAtual && souResponsavel) {
                push(
                    'atribuido',
                    `Chamado #${numero} atribuído a você`,
                    'Você foi designado como responsável por este chamado.',
                    ticket.id,
                );
            }

            // Mudança de status.
            if (previous.status !== ticket.status) {
                if (ticket.status === 'concluido' && (souSolicitante || souResponsavel || isStaff)) {
                    push(
                        'resolvido',
                        `Chamado #${numero} resolvido`,
                        souSolicitante ? 'Seu chamado foi marcado como resolvido.' : `O chamado de ${ticket.solicitante} foi resolvido.`,
                        ticket.id,
                    );
                } else if (ticket.status === 'cancelado' && (souSolicitante || isStaff)) {
                    push(
                        'cancelado',
                        `Chamado #${numero} cancelado`,
                        souSolicitante ? 'Seu chamado foi cancelado.' : `O chamado de ${ticket.solicitante} foi cancelado.`,
                        ticket.id,
                    );
                } else if (isStaff) {
                    push('atualizado', `Chamado #${numero} atualizado`, `O status mudou para "${ticket.status}".`, ticket.id);
                }
            } else if (previous.prioridade !== ticket.prioridade && isStaff) {
                push('atualizado', `Chamado #${numero} atualizado`, `A prioridade foi alterada para "${ticket.prioridade}".`, ticket.id);
            }
        }

        saveSnapshot(currentUser.id, nextSnapshot);

        if (generated.length > 0) {
            setNotifications((current) => {
                const merged = [...generated, ...current];
                const deduped = merged.filter((item, index) => merged.findIndex((other) => other.id === item.id) === index);
                deduped.sort((a, b) => Date.parse(b.timestamp) - Date.parse(a.timestamp));
                const trimmed = deduped.slice(0, MAX_NOTIFICATIONS);
                saveFeed(currentUser.id, trimmed);
                return trimmed;
            });
        }
    }, []);

    const poll = useCallback(async () => {
        const currentUser = userRef.current;
        if (!currentUser) return;

        try {
            const tickets = await fetchChamados();
            setError(null);
            evaluate(tickets);
        } catch (err) {
            const message = err instanceof Error ? err.message : 'Erro ao buscar notificações';
            setError(message);
        } finally {
            setIsLoading(false);
        }
    }, [evaluate]);

    useEffect(() => {
        if (!user) {
            setNotifications([]);
            setIsLoading(false);
            return;
        }

        setNotifications(loadFeed(user.id));
        setIsLoading(true);
        poll();

        const interval = setInterval(poll, POLL_INTERVAL_MS);
        return () => clearInterval(interval);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [user?.id]);

    const markAsRead = useCallback((id: string) => {
        const currentUser = userRef.current;
        if (!currentUser) return;

        setNotifications((current) => {
            const updated = current.map((item) => (item.id === id ? { ...item, read: true } : item));
            saveFeed(currentUser.id, updated);
            return updated;
        });
    }, []);

    const markAllAsRead = useCallback(() => {
        const currentUser = userRef.current;
        if (!currentUser) return;

        setNotifications((current) => {
            const updated = current.map((item) => ({ ...item, read: true }));
            saveFeed(currentUser.id, updated);
            return updated;
        });
    }, []);

    const deleteNotifications = useCallback((ids: string[]) => {
        const currentUser = userRef.current;
        if (!currentUser || ids.length === 0) return;

        const idsToRemove = new Set(ids);
        setNotifications((current) => {
            const updated = current.filter((item) => !idsToRemove.has(item.id));
            saveFeed(currentUser.id, updated);
            return updated;
        });
    }, []);

    const deleteAllNotifications = useCallback(() => {
        const currentUser = userRef.current;
        if (!currentUser) return;

        setNotifications(() => {
            saveFeed(currentUser.id, []);
            return [];
        });
    }, []);

    const unreadCount = notifications.filter((item) => !item.read).length;

    return {
        notifications,
        unreadCount,
        isLoading,
        error,
        markAsRead,
        markAllAsRead,
        deleteNotifications,
        deleteAllNotifications,
        refresh: poll,
    };
}
