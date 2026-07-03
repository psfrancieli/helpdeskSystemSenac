import { motion } from 'framer-motion';
import { Activity, BookCheck, LayoutGrid, ListChecks, Ticket, Users, ClipboardPlus, UserRoundPlus } from 'lucide-react';
import type React from 'react';
import { NavLink } from 'react-router-dom';

import type { DashboardSection } from '../../types/helpdesk';

const items: { key: DashboardSection; label: string; icon: React.ComponentType<{ className?: string }> }[] = [
    { key: 'overview', label: 'Dashboard', icon: LayoutGrid },
    { key: 'usuarios', label: 'Usuário', icon: Users },
    { key: 'chamados', label: 'Chamado', icon: Ticket },
    { key: 'categorias', label: 'Categoria', icon: BookCheck },
    { key: 'historico', label: 'Histórico', icon: ListChecks },
    { key: 'status', label: 'Status', icon: Activity },
    { key: 'criarChamado', label: 'Criar Chamado', icon: ClipboardPlus },
    { key: 'criarUsuario', label: 'Criar Usuário', icon: UserRoundPlus }
];

export function Sidebar() {
    return (
        <aside className="glass-panel hidden w-20 rounded-3xl border-stone-500/30 p-3 lg:block">
            <nav className="flex h-full flex-col gap-2">
                {items.map((item, index) => (
                    <motion.div
                        key={item.key}
                        initial={{ opacity: 0, x: -16 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: 0.15 + index * 0.06, duration: 0.35 }}
                    >
                        <NavLink
                            to={item.key === 'overview' ? '/dashboard' : `/dashboard/${item.key}`}
                            className={({ isActive }) =>
                                `group relative flex h-14 items-center justify-center rounded-2xl border transition-all ${
                                    isActive
                                        ? 'border-amber-400/40 bg-amber-600/15 text-amber-100 shadow-[0_0_18px_rgba(217,119,6,0.28)]'
                                        : 'border-transparent bg-stone-800/35 text-stone-300 hover:border-stone-500/40 hover:bg-stone-700/45 hover:text-white'
                                }`
                            }
                            title={item.label}
                        >
                            {({ isActive }) => (
                                <>
                                    <item.icon className="size-5" />
                                    {isActive ? (
                                        <motion.span
                                            layoutId="menu-indicator"
                                            className="absolute -left-1 h-8 w-1 rounded-full bg-amber-400"
                                        />
                                    ) : null}
                                </>
                            )}
                        </NavLink>
                    </motion.div>
                ))}
            </nav>
        </aside>
    );
}