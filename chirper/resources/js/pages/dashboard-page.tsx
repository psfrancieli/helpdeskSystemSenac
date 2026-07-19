import { AnimatePresence, motion } from "framer-motion";
import { LogOut, Sparkles } from "lucide-react";
import { useEffect, useMemo, useState, type FormEvent } from "react";
import { NavLink, useParams } from "react-router-dom";

import { useAuth } from "../context/auth-context";
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
  metrics,
  statuses,
  users,
} from "../data/mock";
import { useChamados } from "../hooks/useChamados";
import { useTecnicos } from "../hooks/useTecnicos";
import { assignTechnicianToChamado, createChamado } from "../services/chamadoService";
import { createUsuario } from "../services/usuarioService";
import type {
  CreateApiUserInput,
  CreateChamadoInput,
  DashboardSection,
  UserRole,
  TicketPriority,
} from "../types/helpdesk";

interface DashboardPageProps {
  onLogout: () => void;
}

const sectionVisibilityByRole: Record<UserRole, DashboardSection[]> = {
  usuario: ["overview", "chamados", "historico", "criarChamado"],
  tecnico: ["overview", "chamados", "historico", "criarChamado"],
  analista: ["overview", "usuarios", "chamados", "historico", "status", "criarChamado", "criarUsuario"],
  adm: ["overview", "usuarios", "chamados", "historico", "status", "criarChamado", "criarUsuario"],
};

function normalizeSection(sectionParam?: string): DashboardSection {
  const fallback: DashboardSection = "overview";
  const accepted = new Set<DashboardSection>([
    "overview",
    "usuarios",
    "chamados",
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

function createInitialTicketForm(userId: number): CreateChamadoInput {
  return {
    titulo: "",
    descricao: "",
    prioridade: "media",
    patrimonio: "",
    id_categoria: categories[0]?.id ?? 1,
    id_usuario: userId,
    id_responsavel: null,
    status: "pendente",
    data_abertura: new Date().toISOString(),
    data_encerramento: null,
  };
}

function createInitialUserForm(): CreateApiUserInput {
  return {
    nome: "",
    email: "",
    senha: "",
    cpf: "",
    telefone: "",
    nivel: "usuario",
  };
}

function isValidCpf(cpf: string): boolean {
  const digits = cpf.replace(/\D/g, "");

  if (digits.length !== 11) {
    return false;
  }

  if (/^(\d)\1{10}$/.test(digits)) {
    return false;
  }

  const calcDigit = (base: string, factor: number) => {
    let total = 0;

    for (const char of base) {
      total += Number(char) * factor;
      factor -= 1;
    }

    const rest = total % 11;
    return rest < 2 ? 0 : 11 - rest;
  };

  const first = calcDigit(digits.slice(0, 9), 10);
  const second = calcDigit(digits.slice(0, 9) + String(first), 11);

  return digits.endsWith(`${first}${second}`);
}

export function DashboardPage({ onLogout }: DashboardPageProps) {
  const { user } = useAuth();
  const { section: sectionParam } = useParams();
  const section = normalizeSection(sectionParam);
  const currentUser = user;

  if (!currentUser) {
    return null;
  }

  const authUser = currentUser;

  const allowedSections = sectionVisibilityByRole[authUser.nivel] ?? sectionVisibilityByRole.usuario;
  const canAccessCurrentSection = allowedSections.includes(section);
  const [loading, setLoading] = useState(true);
  const {
    chamados,
    isLoading: isChamadosLoading,
    error: chamadosError,
    reloadChamados,
  } = useChamados();
  const {
    tecnicos,
    isLoading: isTecnicosLoading,
    error: tecnicosError,
    reloadTecnicos,
  } = useTecnicos();
  const [ticketForm, setTicketForm] = useState<CreateChamadoInput>(createInitialTicketForm(authUser.id));
  const [isSubmittingTicket, setIsSubmittingTicket] = useState(false);
  const [ticketSubmitError, setTicketSubmitError] = useState<string | null>(null);
  const [ticketSubmitSuccess, setTicketSubmitSuccess] = useState<string | null>(null);
  const [userForm, setUserForm] = useState<CreateApiUserInput>(createInitialUserForm());
  const [isSubmittingUser, setIsSubmittingUser] = useState(false);
  const [userSubmitError, setUserSubmitError] = useState<string | null>(null);
  const [userSubmitSuccess, setUserSubmitSuccess] = useState<string | null>(null);
  const [searchChamado, setSearchChamado] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("todos");
  const [statusFilter, setStatusFilter] = useState("todos");
  const [priorityFilter, setPriorityFilter] = useState("todos");
  const [isAssigningTicketId, setIsAssigningTicketId] = useState<number | null>(null);
  const [assignmentFeedback, setAssignmentFeedback] = useState<string | null>(null);
  const [assignmentError, setAssignmentError] = useState<string | null>(null);

  useEffect(() => {
    setTicketForm(createInitialTicketForm(authUser.id));
  }, [authUser.id]);

  useEffect(() => {
    const timeout = setTimeout(() => setLoading(false), 950);

    return () => clearTimeout(timeout);
  }, []);

  const chamadosByRole = useMemo(() => {
    if (authUser.nivel === "tecnico") {
      const currentName = authUser.nome.trim().toLocaleLowerCase("pt-BR");
      return chamados.filter((item) => (item.responsavel ?? "").trim().toLocaleLowerCase("pt-BR") === currentName);
    }

    if (authUser.nivel === "analista") {
      return chamados.filter((item) => {
        const normalizedStatus = item.status.trim().toLocaleLowerCase("pt-BR");
        return normalizedStatus !== "concluido" && normalizedStatus !== "finalizado";
      });
    }

    return chamados;
  }, [chamados, authUser.nivel, authUser.nome]);

  const dashboardMetrics = [
    {
      ...metrics[0],
      value: `${users.filter((user) => user.ativo).length}`,
    },
    {
      ...metrics[1],
      value: `${chamadosByRole.filter((ticket) => ticket.status === "pendente").length}`,
    },
    {
      ...metrics[2],
      value: `${chamadosByRole.filter((ticket) => ticket.status === "concluido").length}`,
    },
    metrics[3],
  ];

  const chamadoCategories = useMemo(() => {
    const unique = new Set(chamadosByRole.map((item) => item.categoria).filter(Boolean));
    return Array.from(unique).sort((a, b) => a.localeCompare(b, "pt-BR"));
  }, [chamadosByRole]);

  const filteredChamados = useMemo(() => {
    const search = searchChamado.trim().toLowerCase();

    return chamadosByRole.filter((item) => {
      const matchSearch =
        search.length === 0 ||
        item.titulo.toLowerCase().includes(search) ||
        item.patrimonio.toLowerCase().includes(search) ||
        item.solicitante.toLowerCase().includes(search);
      const matchCategory = categoryFilter === "todos" || item.categoria === categoryFilter;
      const matchStatus = statusFilter === "todos" || item.status === statusFilter;
      const matchPriority = priorityFilter === "todos" || item.prioridade === priorityFilter;

      return matchSearch && matchCategory && matchStatus && matchPriority;
    });
  }, [chamadosByRole, searchChamado, categoryFilter, statusFilter, priorityFilter]);

  async function handleAssignTechnician(ticketId: number, technicianId: number) {
    setIsAssigningTicketId(ticketId);
    setAssignmentFeedback(null);
    setAssignmentError(null);

    try {
      await assignTechnicianToChamado({
        id_chamado: ticketId,
        tecnico_id: technicianId,
      });

      setAssignmentFeedback("Técnico atribuído com sucesso.");
      reloadChamados();
      reloadTecnicos();
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao atribuir técnico";
      setAssignmentError(message);
    } finally {
      setIsAssigningTicketId(null);
    }
  }

  async function handleTicketSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSubmittingTicket(true);
    setTicketSubmitError(null);
    setTicketSubmitSuccess(null);

    try {
      await createChamado({
        ...ticketForm,
        id_usuario: authUser.id,
        id_responsavel: null,
        data_abertura: new Date().toISOString(),
        data_encerramento: null,
      });

      setTicketForm(createInitialTicketForm(authUser.id));
      setTicketSubmitSuccess("Chamado enviado com sucesso.");
      reloadChamados();
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao criar chamado";
      setTicketSubmitError(message);
    } finally {
      setIsSubmittingTicket(false);
    }
  }

  async function handleUserSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSubmittingUser(true);
    setUserSubmitError(null);
    setUserSubmitSuccess(null);

    try {
      const cpfDigits = userForm.cpf.replace(/\D/g, "");
      const rawPhoneDigits = userForm.telefone.replace(/\D/g, "");
      const phoneDigits = rawPhoneDigits.startsWith("55") && rawPhoneDigits.length === 13
        ? rawPhoneDigits.slice(2)
        : rawPhoneDigits;
      const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=\[\]{};:'"\\|,.<>/?`~]).{8,64}$/;

      if (cpfDigits.length !== 11) {
        throw new Error("CPF deve conter 11 dígitos.");
      }

      if (!isValidCpf(cpfDigits)) {
        throw new Error("CPF inválido. Verifique os dígitos informados.");
      }

      if (phoneDigits.length !== 11 || phoneDigits[2] !== "9") {
        throw new Error("Telefone inválido. Use DDD + 9 dígitos (ex: 11999998888 ou +55 11 99999-8888).");
      }

      if (!passwordPattern.test(userForm.senha)) {
        throw new Error("Senha inválida. Use 8+ caracteres com maiúscula, minúscula, número e símbolo.");
      }

      await createUsuario({
        ...userForm,
        cpf: cpfDigits,
        telefone: phoneDigits,
        email: userForm.email.trim().toLowerCase(),
        nome: userForm.nome.trim(),
      });

      setUserForm(createInitialUserForm());
      setUserSubmitSuccess("Usuário criado com sucesso.");
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao criar usuário";
      if (message.trim() === "Erro ao criar usuario") {
        setUserSubmitError("Não foi possível criar usuário. Verifique se email, CPF ou telefone já estão cadastrados, ou se os dados estão no formato esperado.");
      } else {
        setUserSubmitError(message);
      }
    } finally {
      setIsSubmittingUser(false);
    }
  }

  return (
    <main className="min-h-screen p-4 md:p-6">
      <div className="mx-auto flex max-w-7xl gap-4 xl:gap-6">
        <Sidebar allowedItems={allowedSections} />
        <section className="w-full space-y-4">
          <motion.div
            initial={{ opacity: 0, y: -16 }}
            animate={{ opacity: 1, y: 0 }}
          >
            <DashboardHeader user={authUser} />
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
              {!canAccessCurrentSection ? (
                <EmptyState
                  title="Acesso restrito"
                  description="Seu perfil não possui permissão para esta seção."
                />
              ) : null}
              {canAccessCurrentSection && section === "overview" ? (
                <div className="space-y-4">
                  {loading || isChamadosLoading ? <SkeletonGrid /> : null}
                  {loading || isChamadosLoading ? null : (
                    <>
                      <div className="grid grid-cols-1 gap-4 xl:grid-cols-4">
                        {dashboardMetrics.map((metric, index) => (
                          <StatCard
                            key={metric.key}
                            metric={metric}
                            index={index}
                          />
                        ))}
                      </div>
                      {chamadosByRole.length === 0 ? (
                        <EmptyState
                          title="Nenhum chamado encontrado"
                          description="Ainda não há chamados retornados pela API."
                        />
                      ) : (
                        <AnimatedTable
                          rows={chamadosByRole}
                          canAssignTechnicians={authUser.nivel === "analista"}
                          technicians={tecnicos}
                          isAssigningTicketId={isAssigningTicketId}
                          assignmentFeedback={assignmentFeedback}
                          assignmentError={assignmentError}
                          technicianLoadError={tecnicosError}
                          techniciansLoading={isTecnicosLoading}
                          onAssignTechnician={handleAssignTechnician}
                          userRole={authUser.nivel}
                        />
                      )}
                    </>
                  )}
                </div>
              ) : null}
              {canAccessCurrentSection && section === "usuarios" ? (
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
              {canAccessCurrentSection && section === "chamados" ? (
                isChamadosLoading ? (
                  <SkeletonGrid />
                ) : chamadosError ? (
                  <EmptyState
                    title="Erro ao carregar chamados"
                    description="Não foi possível conectar com o servidor. Verifique se o backend está rodando."
                  />
                ) : (
                  <div className="space-y-4">
                    <Card>
                      <CardContent className="grid grid-cols-1 gap-3 py-4 md:grid-cols-2 xl:grid-cols-5">
                        <input
                          type="text"
                          value={searchChamado}
                          onChange={(event) => setSearchChamado(event.target.value)}
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                          placeholder="Buscar por título, patrimônio ou solicitante"
                        />

                        <select
                          value={categoryFilter}
                          onChange={(event) => setCategoryFilter(event.target.value)}
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                        >
                          <option value="todos">Todas as categorias</option>
                          {chamadoCategories.map((category) => (
                            <option key={category} value={category}>
                              {category}
                            </option>
                          ))}
                        </select>

                        <select
                          value={statusFilter}
                          onChange={(event) => setStatusFilter(event.target.value)}
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                        >
                          <option value="todos">Todos os status</option>
                          <option value="pendente">Pendente</option>
                          <option value="concluido">Concluído</option>
                          <option value="cancelado">Cancelado</option>
                        </select>

                        <select
                          value={priorityFilter}
                          onChange={(event) => setPriorityFilter(event.target.value)}
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                        >
                          <option value="todos">Todas as prioridades</option>
                          <option value="muito alta">Muito alta</option>
                          <option value="alta">Alta</option>
                          <option value="media">Média</option>
                          <option value="baixa">Baixa</option>
                        </select>

                        <Button
                          type="button"
                          variant="ghost"
                          onClick={() => {
                            setSearchChamado("");
                            setCategoryFilter("todos");
                            setStatusFilter("todos");
                            setPriorityFilter("todos");
                          }}
                        >
                          Limpar filtros
                        </Button>
                      </CardContent>
                    </Card>

                    {chamadosByRole.length === 0 ? (
                      <EmptyState
                        title="Nenhum chamado encontrado"
                        description="O polvo ainda não encontrou chamados cadastrados no sistema."
                      />
                    ) : filteredChamados.length === 0 ? (
                      <EmptyState
                        title="Nenhum chamado para os filtros"
                        description="Ajuste os filtros para visualizar mais resultados."
                      />
                    ) : (
                      <AnimatedTable rows={filteredChamados} />
                    )}
                  </div>
                )
              ) : null}
              {canAccessCurrentSection && section === "status" ? (
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
              {canAccessCurrentSection && section === "historico" ? (
                <EmptyState
                  title="Histórico em preparação"
                  description="O polvo está organizando o timeline de interações para este módulo."
                />
              ) : null}
              {canAccessCurrentSection && section === "criarChamado" ? (
                <Card>
                  <CardContent className="space-y-4 py-4">
                    <div>
                      <p className="text-lg font-semibold text-stone-100">Criar Chamado</p>
                      <p className="text-sm text-stone-400">Envie um novo ticket para o backend.</p>
                    </div>

                    {ticketSubmitError ? (
                      <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {ticketSubmitError}
                      </div>
                    ) : null}

                    {ticketSubmitSuccess ? (
                      <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {ticketSubmitSuccess}
                      </div>
                    ) : null}

                    <form className="space-y-4" onSubmit={handleTicketSubmit}>
                      <label className="block space-y-2">
                        <span className="text-sm text-stone-200">Título</span>
                        <input
                          type="text"
                          value={ticketForm.titulo}
                          onChange={(event) =>
                            setTicketForm((current) => ({ ...current, titulo: event.target.value }))
                          }
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                          placeholder="Notebook sem acesso à VPN"
                          required
                        />
                      </label>

                      <div className="grid gap-4 md:grid-cols-2">
                        <label className="block space-y-2">
                          <span className="text-sm text-stone-200">Patrimônio</span>
                          <input
                            type="text"
                            value={ticketForm.patrimonio}
                            onChange={(event) =>
                              setTicketForm((current) => ({ ...current, patrimonio: event.target.value }))
                            }
                            className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                            placeholder="PATR-9090"
                            required
                          />
                        </label>

                        <label className="block space-y-2">
                          <span className="text-sm text-stone-200">Prioridade</span>
                          <select
                            value={ticketForm.prioridade}
                            onChange={(event) =>
                              setTicketForm((current) => ({
                                ...current,
                                prioridade: event.target.value as TicketPriority,
                              }))
                            }
                            className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                          >
                            <option value="baixa">Baixa</option>
                            <option value="media">Média</option>
                            <option value="alta">Alta</option>
                            <option value="muito alta">Muito alta</option>
                          </select>
                        </label>
                      </div>

                      <label className="block space-y-2">
                        <span className="text-sm text-stone-200">Categoria</span>
                        <select
                          value={ticketForm.id_categoria}
                          onChange={(event) =>
                            setTicketForm((current) => ({
                              ...current,
                              id_categoria: Number(event.target.value),
                            }))
                          }
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                        >
                          {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                              {category.nome}
                            </option>
                          ))}
                        </select>
                      </label>

                      <label className="block space-y-2">
                        <span className="text-sm text-stone-200">Descrição</span>
                        <textarea
                          value={ticketForm.descricao}
                          onChange={(event) =>
                            setTicketForm((current) => ({ ...current, descricao: event.target.value }))
                          }
                          rows={4}
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                          placeholder="Descreva o problema com o máximo de contexto possível."
                          required
                        />
                      </label>

                      <Button type="submit" disabled={isSubmittingTicket}>
                        {isSubmittingTicket ? "Enviando..." : "Criar chamado"}
                      </Button>
                    </form>
                  </CardContent>
                </Card>
              ) : null}

              {canAccessCurrentSection && section === "criarUsuario" ? (
                <Card>
                  <CardContent className="space-y-4 py-4">
                    <div>
                      <p className="text-lg font-semibold text-stone-100">Criar Usuário</p>
                      <p className="text-sm text-stone-400">Cadastre um novo usuário no sistema.</p>
                    </div>

                    {userSubmitError ? (
                      <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {userSubmitError}
                      </div>
                    ) : null}

                    {userSubmitSuccess ? (
                      <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {userSubmitSuccess}
                      </div>
                    ) : null}

                    <form className="space-y-4" onSubmit={handleUserSubmit}>
                      <label className="block space-y-2">
                        <span className="text-sm text-stone-200">Nome</span>
                        <input
                          type="text"
                          value={userForm.nome}
                          onChange={(event) =>
                            setUserForm((current) => ({ ...current, nome: event.target.value }))
                          }
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                          placeholder="Ex: Joao Silva"
                          required
                        />
                      </label>

                      <div className="grid gap-4 md:grid-cols-2">
                        <label className="block space-y-2">
                          <span className="text-sm text-stone-200">Email</span>
                          <input
                            type="email"
                            value={userForm.email}
                            onChange={(event) =>
                              setUserForm((current) => ({ ...current, email: event.target.value }))
                            }
                            className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                            placeholder="joao@empresa.com"
                            required
                          />
                        </label>

                        <label className="block space-y-2">
                          <span className="text-sm text-stone-200">Senha</span>
                          <input
                            type="password"
                            value={userForm.senha}
                            onChange={(event) =>
                              setUserForm((current) => ({ ...current, senha: event.target.value }))
                            }
                            className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                            placeholder="Minimo 8 chars com maiuscula, numero e simbolo"
                            required
                          />
                        </label>
                      </div>

                      <div className="grid gap-4 md:grid-cols-2">
                        <label className="block space-y-2">
                          <span className="text-sm text-stone-200">CPF</span>
                          <input
                            type="text"
                            value={userForm.cpf}
                            onChange={(event) =>
                              setUserForm((current) => ({ ...current, cpf: event.target.value }))
                            }
                            className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                            placeholder="00000000000"
                            required
                          />
                        </label>

                        <label className="block space-y-2">
                          <span className="text-sm text-stone-200">Telefone</span>
                          <input
                            type="text"
                            value={userForm.telefone}
                            onChange={(event) =>
                              setUserForm((current) => ({ ...current, telefone: event.target.value }))
                            }
                            className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                            placeholder="11999998888"
                            required
                          />
                        </label>
                      </div>

                      <label className="block space-y-2">
                        <span className="text-sm text-stone-200">Cargo</span>
                        <select
                          value={userForm.nivel}
                          onChange={(event) =>
                            setUserForm((current) => ({ ...current, nivel: event.target.value as UserRole }))
                          }
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                        >
                          <option value="usuario">Usuário</option>
                          <option value="tecnico">Técnico</option>
                          <option value="analista">Analista</option>
                          <option value="adm">Administrador</option>
                        </select>
                      </label>

                      <Button type="submit" disabled={isSubmittingUser}>
                        {isSubmittingUser ? "Enviando..." : "Criar usuario"}
                      </Button>
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
        ]
          .filter((item) => allowedSections.includes(item.key as DashboardSection))
          .map((item) => (
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
