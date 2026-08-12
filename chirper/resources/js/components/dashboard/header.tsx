import { Bell, LogOut, Search } from 'lucide-react';
import { Link } from 'react-router-dom';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { HelpdeskUser } from '@/types/helpdesk';

interface DashboardHeaderProps {
    user: HelpdeskUser;
    onLogout: () => void;
}

export function DashboardHeader({ user, onLogout }: DashboardHeaderProps) {
    return (
        <header className="glass-panel flex flex-wrap items-center justify-between gap-4 rounded-3xl p-5">
            <div>
                <p className="text-sm text-stone-300">Olá novamente,</p>
                <h1 className="text-2xl font-semibold text-white">{user.nome}</h1>
            </div>
            <div className="flex flex-1 items-center justify-end gap-3">
                {/* <div className="relative max-w-sm flex-1 min-w-48">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                    <Input className="pl-9" placeholder="Busca global..." />
                </div> */}
                <button
                    type="button"
                    className="flex size-11 items-center justify-center rounded-xl border border-stone-600 bg-stone-900/55 text-stone-200 transition-all hover:border-amber-500/50 hover:text-white"
                >
                    <Bell className="size-5" />
                </button>
                <Link
                    to="/dashboard/perfil"
                    aria-label="Meu perfil"
                    className="flex size-11 items-center justify-center rounded-xl border border-stone-600 bg-stone-900/55 text-stone-200 transition-all hover:border-amber-500/50 hover:text-white"
                >
                    <Avatar>
                        <AvatarFallback>{user.nome.split(' ').map((name) => name[0]).slice(0, 2).join('')}</AvatarFallback>
                    </Avatar>
                </Link>
                <Button variant="ghost" onClick={onLogout}>
                    <LogOut className="size-4" />
                    Sair
                </Button>
            </div>
        </header>
    );
}