import { AnimatePresence, motion } from 'framer-motion';
import { Bell, CheckCheck, CheckCircle2, RefreshCcw, Ticket, Trash2, UserCheck, X, XCircle } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { Checkbox } from '@/components/ui/checkbox';
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
    onDeleteSelected: (ids: string[]) => void;
    onDeleteAll: () => void;
}

export function NotificationsPanel({
    notifications,
    unreadCount,
    isLoading,
    error,
    onToggleRead,
    onMarkAllRead,
    onDeleteSelected,
    onDeleteAll,
}: NotificationsPanelProps) {
    const [filter, setFilter] = useState<'todas' | 'nao-lidas'>('todas');
    const [isSelecting, setIsSelecting] = useState(false);
    const [selectedIds, setSelectedIds] = useState<string[]>([]);

    const visible = useMemo(() => {
        if (filter === 'nao-lidas') return notifications.filter((item) => !item.read);
        return notifications;
    }, [notifications, filter]);

    // Se a lista de notificações mudar (ex.: chegou uma nova via polling),
    // descarta seleções que não existem mais para evitar apagar por engano.
    useEffect(() => {
        setSelectedIds((current) => current.filter((id) => notifications.some((item) => item.id === id)));
    }, [notifications]);

    function toggleSelectionMode() {
        setIsSelecting((current) => !current);
        setSelectedIds([]);
    }

    function toggleSelected(id: string) {
        setSelectedIds((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]));
    }

    function toggleSelectAllVisible() {
        const visibleIds = visible.map((item) => item.id);
        const allSelected = visibleIds.length > 0 && visibleIds.every((id) => selectedIds.includes(id));
        setSelectedIds(allSelected ? [] : visibleIds);
    }

    function handleDeleteSelected() {
        if (selectedIds.length === 0) return;
        onDeleteSelected(selectedIds);
        setSelectedIds([]);
        setIsSelecting(false);
    }

    const allVisibleSelected = visible.length > 0 && visible.every((item) => selectedIds.includes(item.id));

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

                <div className="flex shrink-0 items-center gap-1.5">
                    {!isSelecting && unreadCount > 0 ? (
                        <button
                            type="button"
                            onClick={onMarkAllRead}
                            className="flex items-center gap-1.5 rounded-lg border border-stone-600 bg-stone-800/50 px-2.5 py-1.5 text-xs font-medium text-stone-300 transition-colors hover:border-amber-500/50 hover:text-amber-200"
                        >
                            <CheckCheck className="size-3.5" />
                            Marcar todas
                        </button>
                    ) : null}

                    {notifications.length > 0 ? (
                        <button
                            type="button"
                            onClick={toggleSelectionMode}
                            aria-pressed={isSelecting}
                            aria-label={isSelecting ? 'Cancelar seleção' : 'Selecionar notificações para apagar'}
                            title={isSelecting ? 'Cancelar seleção' : 'Selecionar e apagar notificações'}
                            className={cn(
                                'flex size-8 items-center justify-center rounded-lg border transition-colors',
                                isSelecting
                                    ? 'border-rose-500/60 bg-rose-500/15 text-rose-300'
                                    : 'border-stone-600 bg-stone-800/50 text-stone-300 hover:border-rose-500/50 hover:text-rose-300',
                            )}
                        >
                            {isSelecting ? <X className="size-4" /> : <Trash2 className="size-4" />}
                        </button>
                    ) : null}
                </div>
            </div>

            {isSelecting ? (
                <div className="flex items-center justify-between gap-2 px-4 pt-3">
                    <button
                        type="button"
                        onClick={toggleSelectAllVisible}
                        className="flex items-center gap-2 text-xs font-medium text-stone-300 hover:text-white"
                    >
                        <Checkbox checked={allVisibleSelected} onCheckedChange={toggleSelectAllVisible} />
                        {allVisibleSelected ? 'Desmarcar todas' : 'Selecionar todas'}
                    </button>

                    <div className="flex items-center gap-2">
                        {notifications.length > 0 ? (
                            <button
                                type="button"
                                onClick={onDeleteAll}
                                className="text-xs font-medium text-stone-400 hover:text-rose-300"
                            >
                                Limpar tudo
                            </button>
                        ) : null}
                        <button
                            type="button"
                            onClick={handleDeleteSelected}
                            disabled={selectedIds.length === 0}
                            className="notifications-delete-button flex items-center gap-1.5 rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-rose-500 disabled:cursor-not-allowed disabled:bg-rose-600 disabled:text-white"
                        >
                            <Trash2 className="size-3.5" />
                            Limpar {selectedIds.length > 0 ? `(${selectedIds.length})` : ''}
                        </button>
                    </div>
                </div>
            ) : (
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
            )}

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
                                const isSelected = selectedIds.includes(item.id);

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
                                            onClick={() => (isSelecting ? toggleSelected(item.id) : onToggleRead(item.id))}
                                            className={cn(
                                                'flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-stone-700/30',
                                                isSelected && 'bg-rose-500/10',
                                            )}
                                        >
                                            {isSelecting ? (
                                                <span className="mt-1.5 flex size-9 shrink-0 items-center justify-center">
                                                    <Checkbox checked={isSelected} onCheckedChange={() => toggleSelected(item.id)} />
                                                </span>
                                            ) : (
                                                <div className={cn('flex size-9 shrink-0 items-center justify-center rounded-full', style.className)}>
                                                    <Icon className="size-4" strokeWidth={2} />
                                                </div>
                                            )}
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
