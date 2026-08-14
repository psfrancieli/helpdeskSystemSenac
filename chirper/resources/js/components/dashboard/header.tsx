import { AnimatePresence } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';
import { Bell, LogOut, Search } from 'lucide-react';
import { Link } from 'react-router-dom';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NotificationsPanel } from '@/components/dashboard/notifications-panel';
import { useNotifications } from '@/hooks/useNotifications';
import type { HelpdeskUser } from '@/types/helpdesk';

interface DashboardHeaderProps {
    user: HelpdeskUser;
    onLogout: () => void;
}

export function DashboardHeader({ user, onLogout }: DashboardHeaderProps) {
    const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    const { notifications, unreadCount, isLoading, error, markAsRead, markAllAsRead } = useNotifications(user);

    useEffect(() => {
        if (!isNotificationsOpen) return;

        function handleClickOutside(event: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setIsNotificationsOpen(false);
            }
        }

        function handleEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setIsNotificationsOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [isNotificationsOpen]);

    return (
        <header className="glass-panel relative z-40 flex flex-wrap items-center justify-between gap-4 rounded-3xl p-5">
            <div>
                <p className="text-sm text-stone-300">Olá novamente,</p>
                <h1 className="text-2xl font-semibold text-white">{user.nome}</h1>
            </div>
            <div className="flex flex-1 items-center justify-end gap-3">
                <div className="relative min-w-48 max-w-sm flex-1">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                    <Input
                        className="pl-9"
                        placeholder="Busca global..."
                    />
                </div>


                <div ref={containerRef} className="relative">
                    <button
                        type="button"
                        onClick={() =>
                            setIsNotificationsOpen((current) => !current)
                        }
                        aria-haspopup="dialog"
                        aria-expanded={isNotificationsOpen}
                        aria-label="Notificações"
                        className="relative flex size-11 shrink-0 items-center justify-center rounded-xl border border-stone-600 bg-stone-900/55 text-stone-200 transition-all hover:border-amber-500/50 hover:text-white"
                    >
                        <Bell className="size-5" />

                        {unreadCount > 0 && (
                            <span className="absolute -right-1 -top-1 flex min-w-[1.15rem] items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-none text-stone-950">
                            {unreadCount > 9 ? '9+' : unreadCount}
                            </span>
                        )}
                    </button>

                    <AnimatePresence>
                        {isNotificationsOpen && (
                            <NotificationsPanel
                            notifications={notifications}
                            unreadCount={unreadCount}
                            isLoading={isLoading}
                            error={error}
                            onToggleRead={markAsRead}
                            onMarkAllRead={markAllAsRead}
                            />
                        )}
                    </AnimatePresence>
                </div>

                <Link
                    to="/dashboard/perfil"
                    aria-label="Meu perfil"
                    className="flex size-11 shrink-0 items-center justify-center rounded-xl border border-stone-600 bg-stone-900/55 text-stone-200 transition-all hover:border-amber-500/50 hover:text-white"
                >
                    <Avatar>
                    <AvatarFallback>
                        {user.nome
                        .split(' ')
                        .map((name) => name[0])
                        .slice(0, 2)
                        .join('')}
                    </AvatarFallback>
                    </Avatar>
                </Link>


                <Button
                    variant="ghost"
                    onClick={onLogout}
                    className="shrink-0"
                >
                    <LogOut className="size-4" />
                    Sair
                </Button>
            </div>
        </header>
    );
}
