import { AnimatePresence, motion } from "framer-motion";
import { LogOut, Sparkles } from "lucide-react";
import { useEffect, useState } from "react";
import { NavLink, useParams } from "react-router-dom";

import { AnimatedTable } from "../components/dashboard/animated-table";
import { EmptyState } from "../components/dashboard/empty-state";
import { FloatingAssistant } from "../components/dashboard/floating-assistant";
import { DashboardHeader } from "../components/dashboard/header";
import { Sidebar } from "../components/dashboard/sidebar";
import { SkeletonGrid } from "../components/dashboard/skeleton-grid";
import { StatCard } from "../components/dashboard/stat-card";
import { LoadingOctopus } from "../components/mascot/loading-octopus";
import { Badge } from "../components/ui/badge";
import { Button } from "../components/ui/button";
import { Card, CardContent } from "../components/ui/card";
import {
  categories,
  currentUser,
  metrics,
  statuses,
  tickets,
  users,
} from "../data/mock";
import { useChamados } from "../hooks/useChamados";
import type { DashboardSection } from "../types/helpdesk";
import AnimatedButton from "../components/dashboard/button-animated";

interface DashboardPageProps {
  onLogout: () => void;
}

function normalizeSection(sectionParam?: string): DashboardSection {
  const fallback: DashboardSection = "overview";
  const accepted = new Set<DashboardSection>([
    "overview",
    "usuarios",
    "chamados",
    "categorias",
    "historico",
    "status",
    "criarChamado",
    "criarUsuario",
  ]);

  if (!sectionParam || !accepted.has(sectionParam as DashboardSection)) {
    return fallback;
  }

  return sectionParam as DashboardSection;
}

export function DashboardPage({ onLogout }: DashboardPageProps) {
  const { section: sectionParam } = useParams();
  const section = normalizeSection(sectionParam);
  const [loading, setLoading] = useState(true);

  const { chamados, isLoading: isChamadosLoading, error: chamadosError } = useChamados();

  useEffect(() => {
    const timeout = setTimeout(() => setLoading(false), 950);

    return () => clearTimeout(timeout);
  }, []);

  return (
    <main className="min-h-screen p-4 md:p-6">
      <div className="mx-auto flex max-w-7xl gap-4 xl:gap-6">
        <Sidebar />
        <section className="w-full space-y-4">
          <motion.div
            initial={{ opacity: 0, y: -16 }}
            animate={{ opacity: 1, y: 0 }}
          >
            <DashboardHeader user={currentUser} />
          </motion.div>
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-2 rounded-2xl border border-amber-400/30 bg-amber-600/15 px-3 py-2 text-amber-100">
              <Sparkles className="size-4" />
              Modo premium ativo
            </div>
            <Button variant="ghost" onClick={onLogout}>
              <LogOut className="size-4" />
              Sair
            </Button>
          </div>
          <AnimatePresence mode="wait">
            <motion.div
              key={section}
              initial={{ opacity: 0, y: 14 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
            >
              {section === "overview" ? (
                <div className="space-y-4">
                  {loading ? <SkeletonGrid /> : null}
                  {loading ? null : (
                    <>
                      <div className="grid grid-cols-1 gap-4 xl:grid-cols-4">
                        {metrics.map((metric, index) => (
                          <StatCard
                            key={metric.key}
                            metric={metric}
                            index={index}
                          />
                        ))}
                      </div>
                      <AnimatedTable rows={tickets} />
                    </>
                  )}
                </div>
              ) : null}
              {section === "usuarios" ? (
                <Card>
                  <CardContent className="space-y-3">
                    {users.map((user) => (
                      <div
                        key={user.id}
                        className="flex items-center justify-between gap-3 rounded-xl border border-stone-700/70 bg-stone-800/45 p-3"
                      >
                        <div>
                          <p className="font-medium text-white">{user.nome}</p>
                          <p className="text-sm text-stone-300">{user.email}</p>
                        </div>
                        <Badge variant={user.ativo ? "success" : "warning"}>
                          {user.nivel}
                        </Badge>
                      </div>
                    ))}
                  </CardContent>
                </Card>
              ) : null}
              {section === "chamados" ? (
                isChamadosLoading ? (
                  <SkeletonGrid />
                ) : chamadosError ? (
                  <EmptyState
                    title="Erro ao carregar chamados"
                    description="Não foi possível conectar com o servidor. Verifique se o backend está rodando."
                  />
                ) : chamados.length === 0 ? (
                  <EmptyState
                    title="Nenhum chamado encontrado"
                    description="O polvo ainda não encontrou chamados cadastrados no sistema."
                  />
                ) : (
                  <AnimatedTable rows={chamados} />
                )
              ) : null}
              {section === "categorias" ? (
                <Card>
                  <CardContent className="grid grid-cols-2 gap-3 py-4 sm:grid-cols-4">
                    {categories.map((category) => (
                      <div
                        key={category.id}
                        className="rounded-xl border border-stone-700 bg-stone-800/45 p-4 text-center text-stone-200"
                      >
                        {category.nome}
                      </div>
                    ))}
                  </CardContent>
                </Card>
              ) : null}
              {section === "status" ? (
                <Card>
                  <CardContent className="space-y-3 py-4">
                    {statuses.map((status) => (
                      <div
                        key={status.id}
                        className="flex items-center justify-between rounded-xl bg-stone-800/45 p-3"
                      >
                        <p className="capitalize text-stone-100">
                          {status.nome}
                        </p>
                        <Badge variant={status.ativo ? "success" : "warning"}>
                          {status.ativo ? "Ativo" : "Inativo"}
                        </Badge>
                      </div>
                    ))}
                  </CardContent>
                </Card>
              ) : null}
              {section === "historico" ? (
                <EmptyState
                  title="Histórico em preparação"
                  description="O polvo está organizando o timeline de interações para este módulo."
                />
              ) : null}
              {section === "criarChamado" ? (
                <Card>
                  <CardContent className="space-y-3 py-4">
                    
                      <p className="capitalize text-stone-100">Criar Chamado</p>
                      {/* Título, patrimônio, Descrição */}
                      <form>

                        <div className="space-y-12">
                            <div
                            key="criarChamado"
                            style={{fontSize: "30px"}}
                            className="flex items-center text-[14px]"
                            >
                            </div>

                          <div className="border-b border-white/10 pb-12">
                            <div className="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                              <div className="sm:col-span-4">
                                <label
                                  htmlFor="nome"
                                  className="block text-sm/6 font-medium text-white"
                                >
                                  Nome
                                </label>
                                <div className="mt-2">
                                  <div className="flex items-center rounded-md bg-white/5 pl-3 outline-1 -outline-offset-1 outline-white/10 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-500">
                                    <div className="shrink-0 text-base text-gray-400 select-none sm:text-sm/6">
                                    </div>
                                    <input
                                      id="nome"
                                      type="text"
                                      name="nome"
                                      placeholder="janesmith"
                                      className="block min-w-0 grow bg-transparent py-1.5 pr-3 pl-1 text-base text-white placeholder:text-gray-500 focus:outline-none sm:text-sm/6"
                                    />
                                  </div>

                                <div className="col-span-full">
                                  <div className="mt-4"></div>
                                  <label htmlFor="about" className="block text-sm/6 font-medium text-white">Descrição</label>
                                  <p className="mt-3 text-sm/6 text-gray-400">Descreva o problema.</p>
                                  <div className="mt-2">
                                    <textarea id="about" name="about" rows={3} className="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6"></textarea>
                                  </div>
                                </div>
                                <div className="mt-4"></div>
                                  <AnimatedButton />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </form>
                  </CardContent>
                </Card>
              ) : null}

              {section === "criarUsuario" ? (
              <Card className="w-full max-w-lg mx-auto bg-stone-900 border-stone-800 text-stone-100">
                <CardContent className="space-y-4 py-6">
                  <p className="text-xl font-semibold capitalize text-stone-100 border-b border-stone-800 pb-2">
                    Criar Usuário
                  </p>

                  <form className="space-y-4">
                    {/* Nome */}
                    <div className="flex flex-col space-y-1.5">
                      <label htmlFor="name" className="text-sm font-medium text-stone-300">Nome Completo</label>
                      <input 
                        type="text" 
                        id="name" 
                        placeholder="Ex: João Silva" 
                        className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                      />
                    </div           >

                    {/* Email */}
                    <div className="flex flex-col space-y-1.5">
                      <label htmlFor="email" className="text-sm font-medium text-stone-300">Email</label>
                      <input 
                        type="email" 
                        id="email" 
                        placeholder="joao@empresa.com" 
                        className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                      />
                    </div           >

                    {/* Nível e Ativo (Lado a Lado) */}
                    <div className="grid grid-cols-2 gap-4">
                      {/* Nível */}
                      <div className="flex flex-col space-y-1.5">
                        <label htmlFor="level" className="text-sm font-medium text-stone-300">Nível de Acesso</label>
                        <select 
                          id="level" 
                          className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition appearance-none"
                        >
                          <option value="user" className="bg-stone-900">Usuário</option>
                          <option value="manager" className="bg-stone-900">Gerente</option>
                          <option value="admin" className="bg-stone-900">Administrador</option>
                        </select>
                      </div           >

                      {/* Ativo */}
                      <div className="flex flex-col space-y-1.5 justify-end pb-2">
                        <label className="relative flex items-center cursor-pointer select-none">
                          <input type="checkbox" id="active" defaultChecked className="sr-only peer" />
                          <div className="w-9 h-5 bg-stone-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-stone-400 peer-checked:after:bg-stone-100 after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-600"></div>
                          <span className="ms-3 text-sm font-medium text-stone-300">Usuário Ativo</span>
                        </label>
                      </div>
                    </div           >

                    {/* Senha */}
                    <div className="flex flex-col space-y-1.5">
                      <label htmlFor="password" className="text-sm font-medium text-stone-300">Senha Provisória</label>
                      <input 
                        type="password" 
                        id="password" 
                        placeholder="••••••••" 
                        className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                      />
                    </div           >

                    {/* Botão de Ação */}
                    <div className="pt-2">
                      <AnimatedButton />
                    </div>
                  </form>
                </CardContent>
              </Card>
            ) : null}           


            </motion.div>
          </AnimatePresence>
        </section>
      </div>
      <FloatingAssistant />
      <nav className="glass-panel fixed bottom-3 left-1/2 z-40 flex -translate-x-1/2 gap-2 rounded-2xl p-2 lg:hidden">
        {[
          { key: "overview", label: "Home" },
          { key: "chamados", label: "Chamados" },
          { key: "usuarios", label: "Usuários" },
          { key: "criarChamado", label: "Criar Chamado" },
          { key: "criarUsuario", label: "Criar Usuário" },
        ].map((item) => (
          <NavLink
            key={item.key}
            to={
              item.key === "overview" ? "/dashboard" : `/dashboard/${item.key}`
            }
            className={({ isActive }) =>
              `rounded-xl px-3 py-2 text-sm transition-all ${
                isActive ? "bg-amber-600/30 text-white" : "text-stone-300"
              }`
            }
          >
            {item.label}
          </NavLink>
        ))}
      </nav>
      {loading ? <LoadingOctopus /> : null}
    </main>
  );
}
