import { AnimatePresence, motion } from 'framer-motion';
import { Bell, CheckCheck, CheckCircle2, RefreshCcw, Ticket, UserCheck, XCircle } from 'lucide-react';
import { useMemo, useState } from 'react';

import { cn } from '@/lib/utils';
import type { HelpdeskNotification, NotificationType } from '@/types/helpdesk';

const TYPE_STYLES: Record<NotificationType, { icon: typeof Bell; className: string }> = {
    novo: { icon: Ticket, className: 'bg-amber-500/15 text-amber-300' },
    atribuido: { icon: UserCheck, className: 'bg-sky-500/15 text-sky-300' },
    resolvido: { icon: CheckCircle2, className: 'bg-emerald-500/15 text-emerald-300' },
    cancelado: { icon: XCircle, className: 'bg-rose-500/15 text-rose-300' },
    atualizado: { icon: RefreshCcw, className: 'bg-stone-500/15 text-stone-300' },
};

function formatTime(iso: string): string {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

interface NotificationsPanelProps {
    notifications: HelpdeskNotification[];
    unreadCount: number;
    isLoading: boolean;
    error: string | null;
    onToggleRead: (id: string) => void;
    onMarkAllRead: () => void;
}

export function NotificationsPanel({
    notifications,
    unreadCount,
    isLoading,
    error,
    onToggleRead,
    onMarkAllRead,
}: NotificationsPanelProps) {
    const [filter, setFilter] = useState<'todas' | 'nao-lidas'>('todas');

    const visible = useMemo(() => {
        if (filter === 'nao-lidas') return notifications.filter((item) => !item.read);
        return notifications;
    }, [notifications, filter]);

    return (
        <motion.div
            initial={{ opacity: 0, y: -8, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -8, scale: 0.98 }}
            transition={{ duration: 0.15, ease: 'easeOut' }}
            className="absolute right-0 top-[calc(100%+0.75rem)] z-[60] w-[min(38rem,94vw)] overflow-hidden rounded-2xl border border-stone-500/40 bg-stone-900 shadow-2xl"
            role="dialog"
            aria-label="Notificações"
        >
            <div className="flex items-start justify-between gap-3 border-b border-stone-500/20 p-4">
                <div>
                    <div className="mb-2 flex size-8 items-center justify-center rounded-lg bg-amber-600/15">
                        <Bell className="size-4 text-amber-300" strokeWidth={2.2} />
                    </div>
                    <h2 className="text-base font-semibold text-white">Notificações</h2>
                    <p className="mt-0.5 text-xs text-stone-400">Fique por dentro dos seus chamados e atualizações.</p>
                </div>

                {unreadCount > 0 ? (
                    <button
                        type="button"
                        onClick={onMarkAllRead}
                        className="flex shrink-0 items-center gap-1.5 rounded-lg border border-stone-600 bg-stone-800/50 px-2.5 py-1.5 text-xs font-medium text-stone-300 transition-colors hover:border-amber-500/50 hover:text-amber-200"
                    >
                        <CheckCheck className="size-3.5" />
                        Marcar todas
                    </button>
                ) : null}
            </div>

            <div className="flex items-center gap-2 px-4 pt-3">
                {(
                    [
                        { key: 'todas' as const, label: 'Todas' },
                        { key: 'nao-lidas' as const, label: `Não lidas${unreadCount ? ` (${unreadCount})` : ''}` },
                    ]
                ).map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        onClick={() => setFilter(tab.key)}
                        className={cn(
                            'rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
                            filter === tab.key
                                ? 'bg-amber-600 text-white'
                                : 'border border-stone-600 bg-stone-800/40 text-stone-300 hover:text-white',
                        )}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="mt-3 max-h-[20rem] overflow-y-auto">
                {error ? (
                    <div className="px-4 py-6 text-center text-sm text-rose-300">Não foi possível carregar as notificações.</div>
                ) : isLoading && notifications.length === 0 ? (
                    <div className="px-4 py-10 text-center text-sm text-stone-400">Carregando notificações...</div>
                ) : visible.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 px-6 py-12 text-center">
                        <Bell className="size-6 text-stone-500" />
                        <p className="text-sm font-medium text-stone-300">Nenhuma notificação por aqui</p>
                        <p className="text-xs text-stone-500">Novas atualizações de chamados vão aparecer nesta lista.</p>
                    </div>
                ) : (
                    <ul className="divide-y divide-stone-500/10 pb-2">
                        <AnimatePresence initial={false}>
                            {visible.map((item) => {
                                const style = TYPE_STYLES[item.type];
                                const Icon = style.icon;

                                return (
                                    <motion.li
                                        key={item.id}
                                        layout
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        exit={{ opacity: 0 }}
                                    >
                                        <button
                                            type="button"
                                            onClick={() => onToggleRead(item.id)}
                                            className={cn(
                                                'flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-stone-700/30',
                                                !item.read,
                                            )}
                                        >
                                            <div className={cn('flex size-9 shrink-0 items-center justify-center rounded-full', style.className)}>
                                                <Icon className="size-4" strokeWidth={2} />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className={cn('truncate text-sm', item.read ? 'font-medium text-stone-200' : 'font-semibold text-white')}>
                                                        {item.title}
                                                    </p>
                                                    {!item.read ? <span className="size-1.5 shrink-0 rounded-full bg-amber-400" /> : null}
                                                </div>
                                                <p className="truncate text-xs text-stone-400">{item.detail}</p>
                                            </div>
                                            <span className="shrink-0 text-[11px] text-stone-500">{formatTime(item.timestamp)}</span>
                                        </button>
                                    </motion.li>
                                );
                            })}
                        </AnimatePresence>
                    </ul>
                )}
            </div>
        </motion.div>
    );
}
