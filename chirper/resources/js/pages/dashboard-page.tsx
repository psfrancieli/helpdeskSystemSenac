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
import { useTickets } from "../hooks/useTickets";
import { createTicket } from "../services/ticketService";
import { createUsuario } from "../services/usuarioService";
import type { CreateHelpdeskTicket, DashboardSection, CreateHelpdeskUser, TicketPriority, UserRole } from "../types/helpdesk";
import AnimatedButton from "../components/dashboard/button-animated";

interface DashboardPageProps {
  onLogout: () => void;
}

interface TicketFormState {
  titulo: string;
  patrimonio: string;
  descricao: string;
  prioridade: TicketPriority;
  id_categoria: number;
}

interface UserFormState {
  nome: string;
  cpf: string;
  telefone: string;
  email: string;
  senha: string;
  nivel: UserRole;
  ativo: boolean;
}

const initialTicketFormState: TicketFormState = {
  titulo: "",
  patrimonio: "",
  descricao: "",
  prioridade: "media",
  id_categoria: categories[0]?.id ?? 1,
};

const initialUserFormState: UserFormState = {
  nome: "",
  cpf: "",
  telefone: "",
  email: "",
  senha: "",
  nivel: "usuario",
  ativo: true,
};

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
  const [ticketForm, setTicketForm] = useState<TicketFormState>(initialTicketFormState);
  const [ticketError, setTicketError] = useState<string | null>(null);
  const [ticketSuccess, setTicketSuccess] = useState<string | null>(null);
  const [isSubmittingTicket, setIsSubmittingTicket] = useState(false);
  const [isSubmittingUser, setIsSubmittingUser] = useState(false);
  const [userError, setUserError] = useState<string | null>(null);
  const [userSuccess, setUserSuccess] = useState<string | null>(null);
  // const for new users
  const [userForm, setUserForm] = useState<UserFormState>({
    nome: "",
    cpf: "",
    telefone: "",
    email: "",
    senha: "",
    nivel: "usuario",
    ativo: true,
  });


  const {
    tickets: chamados,
    isLoading: isChamadosLoading,
    error: chamadosError,
    refreshTickets,
  } = useTickets();

  useEffect(() => {
    const timeout = setTimeout(() => setLoading(false), 950);

    return () => clearTimeout(timeout);
  }, []);

  function handleTicketFieldChange(
    field: keyof TicketFormState,
    value: string | number,
  ) {
    setTicketForm((current) => ({
      ...current,
      [field]: value,
    }));

    if (ticketError) {
      setTicketError(null);
    }
  }

  function validateTicketForm(): string | null {
    if (ticketForm.titulo.trim().length < 3) {
      return "Informe um título com pelo menos 3 caracteres.";
    }

    if (ticketForm.patrimonio.trim() === "") {
      return "Informe o patrimônio do equipamento.";
    }

    if (ticketForm.descricao.trim().length < 5) {
      return "Informe uma descrição com pelo menos 5 caracteres.";
    }

    if (!ticketForm.id_categoria) {
      return "Selecione uma categoria.";
    }

    return null;
  }

  async function handleCreateTicket(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (isSubmittingTicket) {
      return;
    }

    const validationError = validateTicketForm();

    if (validationError) {
      setTicketSuccess(null);
      setTicketError(validationError);
      return;
    }

    setIsSubmittingTicket(true);
    setTicketError(null);
    setTicketSuccess(null);

    const payload: CreateHelpdeskTicket = {
      titulo: ticketForm.titulo.trim(),
      descricao: ticketForm.descricao.trim(),
      patrimonio: ticketForm.patrimonio.trim(),
      prioridade: ticketForm.prioridade,
      status: "pendente",
      id_categoria: ticketForm.id_categoria,
      id_usuario: currentUser.id,
      id_responsavel: null,
    };

    try {
      const response = await createTicket(payload);
      setTicketForm(initialTicketFormState);
      setTicketSuccess(response.message);
      await refreshTickets();
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao criar chamado";
      setTicketError(message);
    } finally {
      setIsSubmittingTicket(false);
    }
  }

  async function handleCreateUser(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (isSubmittingUser) {
      return;
    }

    setIsSubmittingUser(true);
    setUserError(null);
    setUserSuccess(null);

    if (userForm.nome.trim().length < 3) {
      setUserError("Informe um nome válido.");
      setIsSubmittingUser(false);
      return;
    }

    if (userForm.cpf.trim() === "") {
      setUserError("Informe o CPF.");
      setIsSubmittingUser(false);
      return;
    }

    if (userForm.telefone.trim() === "") {
      setUserError("Informe o telefone.");
      setIsSubmittingUser(false);
      return;
    }

    const payload: CreateHelpdeskUser = {
      nome: userForm.nome.trim(),
      cpf: userForm.cpf.trim(),
      telefone: userForm.telefone.trim(),
      email: userForm.email.trim(),
      senha: userForm.senha.trim(),
      nivel: userForm.nivel,
      ativo: userForm.ativo,
    };

    try {
      const response = await createUsuario(payload);
      setUserForm(initialUserFormState);
      setUserSuccess(response.message);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao criar usuário";
      setUserError(message);
    } finally {
      setIsSubmittingUser(false);
    }

  }

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
                    <form onSubmit={handleCreateTicket} className="space-y-6">
                      <div className="border-b border-white/10 pb-8">
                        <div className="mt-6 grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                          <div className="sm:col-span-4">
                            <label htmlFor="titulo" className="block text-sm/6 font-medium text-white">
                              Título
                            </label>
                            <div className="mt-2">
                              <div className="flex items-center rounded-md bg-white/5 pl-3 outline-1 -outline-offset-1 outline-white/10 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-500">
                                <input
                                  id="titulo"
                                  type="text"
                                  name="titulo"
                                  value={ticketForm.titulo}
                                  onChange={(event) => handleTicketFieldChange("titulo", event.target.value)}
                                  placeholder="Ex: Computador sem acesso ao sistema"
                                  className="block min-w-0 grow bg-transparent py-1.5 pr-3 pl-1 text-base text-white placeholder:text-gray-500 focus:outline-none sm:text-sm/6"
                                />
                              </div>
                            </div>
                          </div>

                          <div className="sm:col-span-2">
                            <label htmlFor="patrimonio" className="block text-sm/6 font-medium text-white">
                              Patrimônio
                            </label>
                            <div className="mt-2">
                              <div className="flex items-center rounded-md bg-white/5 pl-3 outline-1 -outline-offset-1 outline-white/10 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-indigo-500">
                                <input
                                  id="patrimonio"
                                  type="text"
                                  name="patrimonio"
                                  value={ticketForm.patrimonio}
                                  onChange={(event) => handleTicketFieldChange("patrimonio", event.target.value)}
                                  placeholder="Ex: PATR-1001"
                                  className="block min-w-0 grow bg-transparent py-1.5 pr-3 pl-1 text-base text-white placeholder:text-gray-500 focus:outline-none sm:text-sm/6"
                                />
                              </div>
                            </div>
                          </div>
                          {/* PRIORIDADE */}
                          {/* <div className="sm:col-span-3">
                            <label htmlFor="prioridade" className="block text-sm/6 font-medium text-white">
                              Prioridade
                            </label>
                            <select
                              id="prioridade"
                              name="prioridade"
                              value={ticketForm.prioridade}
                              onChange={(event) => handleTicketFieldChange("prioridade", event.target.value as TicketPriority)}
                              className="mt-2 block w-full rounded-md bg-white/5 px-3 py-2 text-base text-white outline-1 -outline-offset-1 outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6"
                            >
                              <option value="baixa" className="bg-stone-900">Baixa</option>
                              <option value="media" className="bg-stone-900">Média</option>
                              <option value="alta" className="bg-stone-900">Alta</option>
                              <option value="muito alta" className="bg-stone-900">Muito alta</option>
                            </select>
                          </div> */}
                          
                          {/* CATEGORIA */}
                          {/* <div className="sm:col-span-3">
                            <label htmlFor="categoria" className="block text-sm/6 font-medium text-white">
                              Categoria
                            </label>
                            <select
                              id="categoria"
                              name="categoria"
                              value={ticketForm.id_categoria}
                              onChange={(event) => handleTicketFieldChange("id_categoria", Number(event.target.value))}
                              className="mt-2 block w-full rounded-md bg-white/5 px-3 py-2 text-base text-white outline-1 -outline-offset-1 outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6"
                            >
                              {categories.map((category) => (
                                <option key={category.id} value={category.id} className="bg-stone-900">
                                  {category.nome}
                                </option>
                              ))}
                            </select>
                          </div> */}

                          <div className="col-span-full">
                            <label htmlFor="descricao" className="block text-sm/6 font-medium text-white">
                              Descrição
                            </label>
                            <p className="mt-2 text-sm/6 text-gray-400">Descreva o problema.</p>
                            <div className="mt-2">
                              <textarea
                                id="descricao"
                                name="descricao"
                                rows={4}
                                value={ticketForm.descricao}
                                onChange={(event) => handleTicketFieldChange("descricao", event.target.value)}
                                className="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6"
                              />
                            </div>
                          </div>
                        </div>
                      </div>

                      <div className="rounded-xl border border-stone-700/70 bg-stone-800/45 px-4 py-3 text-sm text-stone-300">
                        Solicitante atual: <span className="font-medium text-white">{currentUser.nome}</span>
                      </div>

                      {ticketError ? (
                        <div className="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                          {ticketError}
                        </div>
                      ) : null}

                      {ticketSuccess ? (
                        <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                          {ticketSuccess}
                        </div>
                      ) : null}

                      <div className="pt-2">
                        <AnimatedButton
                          type="submit"
                          disabled={isSubmittingTicket}
                          label={isSubmittingTicket ? "Criando..." : "Criar chamado"}
                        />
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

                  <form className="space-y-4" onSubmit={handleCreateUser}>
                    {/* Nome */}
                    <div className="flex flex-col space-y-1.5">
                      <label htmlFor="name" className="text-sm font-medium text-stone-300">Nome Completo</label>
                      <input 
                        type="text" 
                        id="name"
                        value={userForm.nome}
                        onChange={(event) => setUserForm((current) => ({ ...current, nome: event.target.value }))} 
                        placeholder="Ex: João Silva" 
                        className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                      />
                    </div           >

                    {/* Email */}
                    <div className="flex flex-col space-y-1.5">
                      <label htmlFor="cpf" className="text-sm font-medium text-stone-300">CPF</label>
                      <input
                        type="text"
                        id="cpf"
                        value={userForm.cpf}
                        onChange={(event) => setUserForm((current) => ({ ...current, cpf: event.target.value }))}
                        placeholder="000.000.000-00"
                        className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                      />
                    </div>

                    <div className="flex flex-col space-y-1.5">
                      <label htmlFor="telefone" className="text-sm font-medium text-stone-300">Telefone</label>
                      <input
                        type="text"
                        id="telefone"
                        value={userForm.telefone}
                        onChange={(event) => setUserForm((current) => ({ ...current, telefone: event.target.value }))}
                        placeholder="(11) 99999-9999"
                        className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                      />
                    </div>

                    {/* Email */}
                    <div className="flex flex-col space-y-1.5">
                      <label htmlFor="email" className="text-sm font-medium text-stone-300">Email</label>
                      <input 
                        type="email" 
                        id="email"
                        value={userForm.email}
                        onChange={(event) => setUserForm((current) => ({ ...current, email: event.target.value }))} 
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
                          value={userForm.nivel}
                          onChange={(event) => setUserForm((current) => ({ ...current, nivel: event.target.value as UserRole }))} 
                          className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition appearance-none"
                        >
                          <option value="usuario" className="bg-stone-900">Usuário</option>
                          <option value="tecnico" className="bg-stone-900">Técnico</option>
                          <option value="analista" className="bg-stone-900">Analista</option>
                          <option value="adm" className="bg-stone-900">Administrador</option>
                        </select>
                      </div>

                      {/* Ativo */}
                      <div className="flex flex-col space-y-1.5 justify-end pb-2">
                        <label className="relative flex items-center cursor-pointer select-none">
                          <input type="checkbox" id="active" checked={userForm.ativo} onChange={(event) => setUserForm((current) => ({ ...current, ativo: event.target.checked }))} className="sr-only peer" />
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
                        value={userForm.senha}
                        onChange={(event) => setUserForm((current) => ({ ...current, senha: event.target.value }))}
                        placeholder="••••••••" 
                        className="w-full px-3 py-2 bg-stone-950 border border-stone-800 rounded-md text-sm text-stone-100 placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                      />
                    </div           >

                    {userError ? (
                      <div className="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                        {userError}
                      </div>
                    ) : null}

                    {userSuccess ? (
                      <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {userSuccess}
                      </div>
                    ) : null}

                    {/* Botão de Ação */}
                    <div className="pt-2">
                      <AnimatedButton type="submit" disabled={isSubmittingUser} label={isSubmittingUser ? "Criando..." : "Criar usuário"} />
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

