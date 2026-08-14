import { AnimatePresence, motion } from "framer-motion";
import { useEffect, useMemo, useState, type FormEvent } from "react";
import { NavLink, useParams } from "react-router-dom";
import { useAuth } from "../context/auth-context";
import { AnimatedTable } from "../components/dashboard/animated-table";
import { EmptyState } from "../components/dashboard/empty-state";
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
import { useCategorias } from "../hooks/useCategorias";
import { useUsuarios } from "../hooks/useUsuarios";
import { useChamados } from "../hooks/useChamados";
import { useTecnicos } from "../hooks/useTecnicos";
import { assignTechnicianToChamado, createChamado } from "../services/chamadoService";
import { createUsuario, updateMeuTelefone  } from "../services/usuarioService";
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
  usuario: ["overview", "chamados", "historico", "criarChamado", "perfil"],
  tecnico: ["overview", "chamados", "historico", "criarChamado", "perfil"],
  analista: ["overview", "usuarios", "chamados", "historico", "criarChamado", "criarUsuario", "perfil"],
  adm: ["overview", "usuarios", "chamados", "historico", "criarChamado", "criarUsuario", "perfil"],
};

function normalizeSection(sectionParam?: string): DashboardSection {
  const fallback: DashboardSection = "overview";
  const accepted = new Set<DashboardSection>([
    "overview",
    "usuarios",
    "chamados",
    "historico",
    "criarChamado",
    "criarUsuario",
    "perfil"
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
    id_categoria: 0,
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

function formatTelefoneDisplay(digits: string): string {
  if (digits.length === 0) return "";
  if (digits.length <= 2) return `(${digits}`;
  if (digits.length <= 7) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
  return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7, 11)}`;
}

export function DashboardPage({ onLogout }: DashboardPageProps) {
  const { user, refreshUser } = useAuth();
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
    usuarios,
    isLoading: isUsuariosLoading,
    error: usuariosError,
  } = useUsuarios();
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
  const {
    categorias,
    isLoading: isCategoriasLoading,
  } = useCategorias();
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
  const [profilePhone, setProfilePhone] = useState(authUser.telefone.replace(/\D/g, ""));
  const [isSubmittingProfile, setIsSubmittingProfile] = useState(false);
  const [profileSubmitError, setProfileSubmitError] = useState<string | null>(null);
  const [profileSubmitSuccess, setProfileSubmitSuccess] = useState<string | null>(null);
  const [isEditingPhone, setIsEditingPhone] = useState(false);

  useEffect(() => {
    setProfilePhone(authUser.telefone.replace(/\D/g, ""));
  }, [authUser.telefone]);

  useEffect(() => {
    setTicketForm(createInitialTicketForm(authUser.id));
  }, [authUser.id]);

  useEffect(() => {
    const timeout = setTimeout(() => setLoading(false), 950);

    return () => clearTimeout(timeout);
  }, []);
  useEffect(() => {
    if (categorias.length > 0 && ticketForm.id_categoria === 0) {
      setTicketForm((current) => ({ ...current, id_categoria: categorias[0].id }));
    }
  }, [categorias, ticketForm.id_categoria]);

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
      value: `${usuarios.filter((user) => user.ativo).length}`,
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

  const usuariosOrdenados = useMemo(() => {
    return [...usuarios].sort((a, b) => a.nome.localeCompare(b.nome, "pt-BR"));
  }, [usuarios]);

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

  async function handleProfileSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsSubmittingProfile(true);
    setProfileSubmitError(null);
    setProfileSubmitSuccess(null);

    try {
      const result = await updateMeuTelefone(profilePhone);
      setProfilePhone(result.telefone.replace(/\D/g, ""));
      await refreshUser();
      setProfileSubmitSuccess("Telefone atualizado com sucesso.");
      setIsEditingPhone(false);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao atualizar telefone";
      setProfileSubmitError(message);
    } finally {
      setIsSubmittingProfile(false);
    }
  }

  return (
    <main className="min-h-screen p-4 md:p-6">
      <div className="mx-auto flex max-w-7xl gap-4 xl:gap-6">
        <Sidebar allowedItems={allowedSections} />
        <section className="w-full space-y-4">
          <motion.div
            className="relative z-40"
            initial={{ opacity: 0, y: -16 }}
            animate={{ opacity: 1, y: 0 }}
          >
          <DashboardHeader user={authUser} onLogout={onLogout} />
          </motion.div>
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
                isUsuariosLoading ? (
                  <SkeletonGrid />
                ) : usuariosError ? (
                  <EmptyState
                    title="Erro ao carregar usuários"
                    description="Não foi possível conectar com o servidor. Verifique se o backend está rodando."
                  />
                ) : usuarios.length === 0 ? (
                  <EmptyState
                    title="Nenhum usuário encontrado"
                    description="Ainda não há usuários cadastrados no sistema."
                  />
                ) : (
                  <Card>
                    <CardContent className="space-y-3">
                    {usuariosOrdenados.map((usuario) => (
                      <div
                        key={usuario.id}
                        className="flex items-center justify-between gap-3 rounded-xl border border-stone-700/70 bg-stone-800/45 p-3"
                      >
                        <div>
                          <p className="font-medium text-white">{usuario.nome}</p>
                          <p className="text-sm text-stone-300">{usuario.email}</p>
                        </div>
                        <Badge variant={usuario.ativo ? "success" : "warning"}>
                          {usuario.nivel}
                        </Badge>
                      </div>
                    ))}
                    </CardContent>
                  </Card>
                )
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
              {/* {canAccessCurrentSection && section === "status" ? (
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
              ) : null} */}
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
                          disabled={isCategoriasLoading || categorias.length === 0}
                          className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                        >
                          {isCategoriasLoading ? (
                            <option value={0}>Carregando categorias...</option>
                          ) : categorias.length === 0 ? (
                            <option value={0}>Nenhuma categoria disponível</option>
                          ) : (
                            categorias.map((category) => (
                              <option key={category.id} value={category.id}>
                                {category.nome}
                              </option>
                            ))
                          )}
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

              {canAccessCurrentSection && section === "perfil" ? (
                <Card>
                  <CardContent className="space-y-4 py-4">
                    <div>
                      <p className="text-lg font-semibold text-stone-100">Meu Perfil</p>
                      <p className="text-sm text-stone-400">Visualize seus dados e mantenha seu telefone atualizado.</p>
                    </div>

                    {profileSubmitError ? (
                      <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {profileSubmitError}
                      </div>
                    ) : null}

                    {profileSubmitSuccess ? (
                      <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {profileSubmitSuccess}
                      </div>
                    ) : null}

                    <div className="space-y-2">
                      <div>
                        <Badge variant="success">{authUser.nivel}</Badge>
                      </div>
                    </div>
                    
                    <div className="grid gap-4 md:grid-cols-2">
                      <div className="space-y-2">
                        <span className="text-sm text-stone-200">Nome</span>
                        <div className="w-full rounded-xl border border-stone-700 bg-stone-900/60 px-3 py-2 text-stone-300">
                          {authUser.nome}
                        </div>
                      </div>

                      <div className="space-y-2">
                        <span className="text-sm text-stone-200">Email</span>
                        <div className="w-full rounded-xl border border-stone-700 bg-stone-900/60 px-3 py-2 text-stone-300">
                          {authUser.email}
                        </div>
                      </div>
                    </div>

                    <div className="space-y-2">
                      <span className="text-sm text-stone-200">Telefone</span>

                      {isEditingPhone ? (
                        <form className="flex flex-wrap items-center gap-2" onSubmit={handleProfileSubmit}>
                          <input
                            type="text"
                            value={formatTelefoneDisplay(profilePhone)}
                            onChange={(event) => setProfilePhone(event.target.value.replace(/\D/g, "").slice(0, 11))}
                            inputMode="numeric"
                            autoFocus
                            className="min-w-0 flex-1 rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                            placeholder="(11) 99999-9999"
                            required
                          />
                          <Button type="submit" disabled={isSubmittingProfile}>
                            {isSubmittingProfile ? "Salvando..." : "Salvar"}
                          </Button>
                          <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                              setIsEditingPhone(false);
                              setProfilePhone(authUser.telefone.replace(/\D/g, ""));
                              setProfileSubmitError(null);
                            }}
                          >
                            Cancelar
                          </Button>
                        </form>
                      ) : (
                        <div className="flex items-center justify-between gap-3 rounded-xl border border-stone-700 bg-stone-900/60 px-3 py-2">
                          <span className="text-stone-300">{formatTelefoneDisplay(profilePhone)}</span>
                          <button
                            type="button"
                            onClick={() => setIsEditingPhone(true)}
                            className="text-sm font-medium text-amber-400 transition-colors hover:text-amber-300"
                          >
                            Editar
                          </button>
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ) : null}          
            </motion.div>
          </AnimatePresence>
        </section>
      </div>
      {/* <FloatingAssistant /> */}
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
