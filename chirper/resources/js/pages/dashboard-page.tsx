import { AnimatePresence, motion } from "framer-motion";
import { useEffect, useMemo, useState, type FormEvent } from "react";
import { KeyRound } from "lucide-react";
import type { HelpdeskUser } from '@/types/helpdesk';
import {getHistoryByTicketId,createHistoryComment,type HistoryItem,} from "../services/historicoService";
import { NavLink, useNavigate, useParams, useSearchParams } from "react-router-dom";
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
import { RelatoriosPage } from './relatorios-page';
import { metrics } from "../data/mock";
import { useCategorias } from "../hooks/useCategorias";
import { useUsuarios } from "../hooks/useUsuarios";
import { useChamados } from "../hooks/useChamados";
import { useTecnicos } from "../hooks/useTecnicos";
import { assignTechnicianToChamado, createChamado } from "../services/chamadoService";
import { alterarNivelUsuario, createUsuario, resetarSenhaUsuario, updateMeuTelefone } from "../services/usuarioService";
import type { CreateApiUserInput, CreateChamadoInput, DashboardSection, UserRole, TicketPriority, HelpdeskTicket } from "../types/helpdesk";

interface DashboardPageProps {
  onLogout: () => void;
}

const sectionVisibilityByRole: Record<UserRole, DashboardSection[]> = {
  usuario: ["overview", "chamados", "criarChamado", "perfil"],
  tecnico: ["overview", "chamados", "criarChamado", "perfil"],
  analista: ["overview", "usuarios", "chamados", "criarChamado", "criarUsuario", "perfil"],
  adm: ["overview", "usuarios", "chamados", "criarChamado", "criarUsuario", "perfil", "relatorios"],
};

function normalizeSection(sectionParam?: string): DashboardSection {
  const fallback: DashboardSection = "overview";
  const accepted = new Set<DashboardSection>([
    "overview",
    "usuarios",
    "chamados",
    "criarChamado",
    "criarUsuario",
    "perfil",
    "relatorios"
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

// VALORES INICIAS DO USUÁRIO
function createInitialUserForm(): CreateApiUserInput {
  return {
    nome: "",
    email: "",
    senha: "Help123@",
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


function priorityVariant(priority: HelpdeskTicket["prioridade"]) {
  if (priority === "muito alta" || priority === "alta") return "danger";
  if (priority === "media") return "warning";
  return "default";
}

interface ChamadoDetalhesProps {
  chamado: HelpdeskTicket;
  onVoltar: () => void;
  currentUser: HelpdeskUser;
}

function ChamadoDetalhes({ chamado, onVoltar, currentUser }: ChamadoDetalhesProps) {
  const [historico, setHistorico] = useState<HistoryItem[]>([]);
  const [isLoadingHistorico, setIsLoadingHistorico] = useState(true);
  const [historicoError, setHistoricoError] = useState<string | null>(null);

  const [comentario, setComentario] = useState("");
  const [isSubmittingComentario, setIsSubmittingComentario] = useState(false);
  const [comentarioError, setComentarioError] = useState<string | null>(null);

  async function carregarHistorico() {
    setIsLoadingHistorico(true);
    setHistoricoError(null);
    try {
      const dados = await getHistoryByTicketId(chamado.id);
      setHistorico(dados);
    } catch (error) {
      setHistoricoError(error instanceof Error ? error.message : "Erro ao carregar histórico");
    } finally {
      setIsLoadingHistorico(false);
    }
  }

  useEffect(() => {
    carregarHistorico();
  }, [chamado.id]);

  const nomeNormalizado = currentUser.nome.trim().toLocaleLowerCase("pt-BR");
  const ehTecnicoDoChamado =
    currentUser.nivel === "tecnico" &&
    (chamado.responsavel ?? "").trim().toLocaleLowerCase("pt-BR") === nomeNormalizado;
  const ehSolicitante =
    currentUser.nivel === "usuario" &&
    chamado.solicitante.trim().toLocaleLowerCase("pt-BR") === nomeNormalizado;
  const podeComentar = ehTecnicoDoChamado || ehSolicitante;

  async function handleComentarioSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (comentario.trim() === "") return;

    setIsSubmittingComentario(true);
    setComentarioError(null);
    try {
      await createHistoryComment(chamado.id, comentario.trim());
      setComentario("");
      await carregarHistorico();
    } catch (error) {
      setComentarioError(error instanceof Error ? error.message : "Erro ao enviar comentário");
    } finally {
      setIsSubmittingComentario(false);
    }
  }

  return (
    <motion.div
      initial={{ opacity: 0, x: 20 }}
      animate={{ opacity: 1, x: 0 }}
      exit={{ opacity: 0, x: -20 }}
      className="space-y-4"
    >
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-sm text-stone-400">Chamado #{chamado.id}</p>
          <h1 className="text-2xl font-semibold text-white">{chamado.titulo}</h1>
        </div>
        <Button type="button" variant="ghost" onClick={onVoltar}>
          ← Voltar
        </Button>
      </div>
      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardContent className="space-y-5 py-5">
            <div>
              <p className="text-sm text-stone-400">ID do chamado</p>
              <p className="mt-1 text-stone-200">#{chamado.id}</p>
            </div>
            <div>
              <p className="text-sm text-stone-400">Descrição</p>
              <p className="mt-1 text-stone-200">{chamado.descricao}</p>
            </div>

            <div>
              <p className="text-sm text-stone-400">Histórico</p>
              <div className="mt-2 space-y-3 rounded-xl border border-stone-700 bg-stone-900/60 p-4">
                {isLoadingHistorico ? (
                  <p className="text-sm text-stone-400">Carregando histórico...</p>
                ) : historicoError ? (
                  <p className="text-sm text-red-300">{historicoError}</p>
                ) : historico.length === 0 ? (
                  <p className="text-sm text-stone-400">Nenhum registro de histórico ainda.</p>
                ) : (
                  historico
                    .slice()
                    .sort((a, b) => new Date(a.data).getTime() - new Date(b.data).getTime())
                    .map((item, index) => (
                      <div key={`${item.data}-${index}`} className="border-l-2 border-amber-500 pl-4">
                        <p className="text-sm font-medium text-white">{item.descricao}</p>
                        <p className="mt-1 text-xs text-stone-400">
                          {new Date(item.data).toLocaleString("pt-BR")}
                        </p>
                      </div>
                    ))
                )}
              </div>

              {podeComentar ? (
                <form className="mt-3 space-y-2" onSubmit={handleComentarioSubmit}>
                  {comentarioError ? (
                    <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-100">
                      {comentarioError}
                    </div>
                  ) : null}
                  <textarea
                    value={comentario}
                    onChange={(event) => setComentario(event.target.value)}
                    rows={3}
                    className="w-full rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100 placeholder:text-stone-500"
                    placeholder="Como está indo o chamado?"
                    required
                  />
                  <Button type="submit" disabled={isSubmittingComentario}>
                    {isSubmittingComentario ? "Enviando..." : "Adicionar comentário"}
                  </Button>
                </form>
              ) : null}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="space-y-5 py-5">
            <div>
              <p className="text-sm text-stone-400">Status</p>
              <p className="mt-1 capitalize text-stone-200">{chamado.status}</p>
            </div>
            <div>
              <p className="text-sm text-stone-400">Prioridade</p>
              <div className="mt-2">
                <Badge variant={priorityVariant(chamado.prioridade)}>{chamado.prioridade}</Badge>
              </div>
            </div>
            <div>
              <p className="text-sm text-stone-400">Categoria</p>
              <p className="mt-1 text-stone-200">{chamado.categoria || "Não informada"}</p>
            </div>
            <div>
              <p className="text-sm text-stone-400">Patrimônio</p>
              <p className="mt-1 text-stone-200">{chamado.patrimonio}</p>
            </div>
            <div>
              <p className="text-sm text-stone-400">Solicitante</p>
              <p className="mt-1 text-stone-200">{chamado.solicitante || "Não informado"}</p>
            </div>
            <div>
              <p className="text-sm text-stone-400">Responsável</p>
              <p className="mt-1 text-stone-200">{chamado.responsavel || "A definir"}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </motion.div>
  );
}

export function DashboardPage({ onLogout }: DashboardPageProps) {
  const { user, refreshUser } = useAuth();
  const { section: sectionParam } = useParams();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const section = normalizeSection(sectionParam);
  const chamadoSelecionadoId = Number(searchParams.get("chamado"));
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
    reloadUsuarios,
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
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [selectedNivel, setSelectedNivel] = useState<UserRole>("usuario");
  const [isSubmittingNivel, setIsSubmittingNivel] = useState(false);
  const [nivelError, setNivelError] = useState<string | null>(null);
  const [nivelSuccess, setNivelSuccess] = useState<string | null>(null);
  const [isResettingSenha, setIsResettingSenha] = useState(false);
  const [resetSenhaError, setResetSenhaError] = useState<string | null>(null);
  const [resetSenhaSuccess, setResetSenhaSuccess] = useState<string | null>(null);

  const viewingUser = selectedUserId !== null ? usuarios.find((usuario) => usuario.id === selectedUserId) ?? null : null;

  useEffect(() => {
    if (viewingUser) {
      setSelectedNivel(viewingUser.nivel);
    }
    setNivelError(null);
    setNivelSuccess(null);
    setResetSenhaError(null);
    setResetSenhaSuccess(null);
  }, [selectedUserId]);

  useEffect(() => {
    setProfilePhone(authUser.telefone.replace(/\D/g, ""));
  }, [authUser.telefone]);

const [isUpdatingStatusId, setIsUpdatingStatusId] = useState<number | null>(null);

const handleUpdateStatus = async (ticketId: number, novoStatus: string) => {
    setIsUpdatingStatusId(ticketId); 

    try {
        const resposta = await fetch('/api/chamados/atualizar-status', {
            method: 'POST', 
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include', 
            
            body: JSON.stringify({
                id_chamado: ticketId,
                status: novoStatus
            })
        });

        const dados = await resposta.json();

        if (!dados.success) {
            throw new Error(dados.message || 'Erro desconhecido ao atualizar.');
        }

        console.log("Status atualizado:", dados.data);

    } catch (erro: any) {
        console.error("Erro na atualização:", erro);
        alert(erro.message || "Erro ao atualizar o status do chamado.");
    } finally {
        setIsUpdatingStatusId(null); 
    }
};

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

    if (authUser.nivel === "usuario") {
      const currentName = authUser.nome.trim().toLocaleLowerCase("pt-BR");
      return chamados.filter((item) => item.solicitante.trim().toLocaleLowerCase("pt-BR") === currentName);
    }

    if (authUser.nivel === "analista") {
  return chamados;
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
    {
      ...metrics[3],
      value: `${chamadosByRole.filter((ticket) => ticket.status === "não resolvido").length}`,
    }
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

  const chamadoSelecionado = chamados.find((item) => item.id === chamadoSelecionadoId);

  function handleTicketClick(ticket: HelpdeskTicket) {
    navigate(`/dashboard/${section}?chamado=${ticket.id}`);
  }

  function handleVoltarChamado() {
      navigate(-1);
  }

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

      setTimeout(() => {
        setAssignmentFeedback(null);
      }, 3500);

      reloadChamados();
      reloadTecnicos();
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao atribuir técnico";
      setAssignmentError(message);

      setTimeout(() => {
        setAssignmentFeedback(null);
      }, 3000);

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
      setTimeout(() => {
        setTicketSubmitSuccess(null);
      }, 3000);
      reloadChamados();
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao criar chamado";
      setTicketSubmitError(message);
      setTimeout(() => {
        setTicketSubmitSuccess(null);
      }, 3500);
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

  async function handleAlterarNivel(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!viewingUser) return;

    setIsSubmittingNivel(true);
    setNivelError(null);

    try {
      await alterarNivelUsuario(viewingUser.id, selectedNivel);
      reloadUsuarios();
      setNivelSuccess("Nível atualizado com sucesso.");
      setTimeout(() => {
        setAssignmentFeedback(null);
      }, 3500);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao atualizar nível";
      setNivelError(message);
    } finally {
      setIsSubmittingNivel(false);
    }
  }

  async function handleResetarSenha() {
    if (!viewingUser) return;

    setIsResettingSenha(true);
    setResetSenhaError(null);
    setResetSenhaSuccess(null);

    try {
      await resetarSenhaUsuario(viewingUser.id);
      setResetSenhaSuccess("Senha redefinida para a senha padrão.");
      setTimeout(() => {
        setAssignmentFeedback(null);
      }, 3500);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Erro ao redefinir senha";
      setResetSenhaError(message);
    } finally {
      setIsResettingSenha(false);
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
              key={chamadoSelecionadoId ? `chamado-${chamadoSelecionadoId}` : section}
              initial={{ opacity: 0, y: 14 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
            >
              {chamadoSelecionadoId ? (
                chamadoSelecionado ? (
                  <ChamadoDetalhes
                      chamado={chamadoSelecionado}
                      onVoltar={handleVoltarChamado}
                      currentUser={authUser}
                  />
                ) : (
                  <EmptyState
                    title="Chamado não encontrado"
                    description="Não foi possível encontrar este chamado."
                  />
                )
              ) : (
                <>
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
                      />
                      )}
                    </>
                  )}
                </div>
              ) : null}
              {canAccessCurrentSection && section === "usuarios" && selectedUserId === null ? (
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
                          onClick={() => setSelectedUserId(usuario.id)}
                          className="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-stone-700/70 bg-stone-800/45 p-3 transition-colors hover:border-amber-500/40"
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
              {canAccessCurrentSection && section === "usuarios" && selectedUserId !== null ? (
                <Card>
                  <CardContent className="space-y-4 py-4">
                    <div className="flex items-center justify-between gap-3">
                      <div className="flex items-center gap-4">
                        <div className="flex size-14 items-center justify-center rounded-2xl border border-amber-500/30 bg-amber-600/15 text-lg font-semibold text-amber-200">
                          {viewingUser?.nome
                            .split(" ")
                            .map((n) => n[0])
                            .slice(0, 2)
                            .join("") ?? "?"}
                        </div>
                        <div>
                          <p className="text-xl font-semibold text-stone-100">{viewingUser?.nome ?? "Carregando..."}</p>
                        </div>
                      </div>
                      <Button variant="ghost" onClick={() => setSelectedUserId(null)}>
                        ⮌  Voltar
                      </Button>
                    </div>

                    {!viewingUser ? (
                      isUsuariosLoading ? (
                        <SkeletonGrid />
                      ) : (
                        <EmptyState
                          title="Usuário não encontrado"
                          description="Não foi possível localizar esse usuário na lista."
                        />
                      )
                    ) : (
                      <>
                        <div className="grid gap-4 md:grid-cols-2">
                          <div className="space-y-2">
                            <span className="text-sm text-stone-200">Email</span>
                            <div className="w-full rounded-xl border border-stone-700 bg-stone-900/60 px-3 py-2 text-stone-300">
                              {viewingUser.email}
                            </div>
                          </div>

                          <div className="space-y-2">
                            <span className="text-sm text-stone-200">Telefone</span>
                            <div className="w-full rounded-xl border border-stone-700 bg-stone-900/60 px-3 py-2 text-stone-300">
                              {viewingUser.telefone || "Não informado"}
                            </div>
                          </div>
                        </div>

                        <div className="space-y-2 border-t border-stone-700/60 pt-4">
                          <span className="text-sm text-stone-200">Alterar cargo</span>

                          {nivelError ? (
                            <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                              {nivelError}
                            </div>
                          ) : null}

                          {nivelSuccess ? (
                            <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                              {nivelSuccess}
                            </div>
                          ) : null}

                          <form className="flex flex-wrap items-center gap-2" onSubmit={handleAlterarNivel}>
                            <select
                              value={selectedNivel}
                              onChange={(event) => setSelectedNivel(event.target.value as UserRole)}
                              className="rounded-xl border border-stone-700 bg-stone-950 px-3 py-2 text-stone-100"
                            >
                              <option value="usuario">Usuário</option>
                              <option value="tecnico">Técnico</option>
                              <option value="analista">Analista</option>
                              <option value="adm">Administrador</option>
                            </select>

                            <Button type="submit" disabled={isSubmittingNivel || selectedNivel === viewingUser.nivel}>
                              {isSubmittingNivel ? "Salvando..." : "Salvar cargo"}
                            </Button>
                          </form>
                        </div>

                        <div className="space-y-2 border-t border-stone-700/60 pt-4">
                          <span className="text-sm text-stone-200">Senha</span>
                            <p className="text-xs text-stone-400">
                              Atenção: esta ação é não é reversível e redefine a senha do usuário para o valor padrão do sistema.
                            </p>
                          {resetSenhaError ? (
                            <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                              {resetSenhaError}
                            </div>
                          ) : null}

                          {resetSenhaSuccess ? (
                            <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                              {resetSenhaSuccess}
                            </div>
                          ) : null}

                          <div>
                            <Button
                              type="button"
                              variant="ghost"
                              disabled={isResettingSenha}
                              onClick={handleResetarSenha}
                              className="border border-stone-500/50 hover:border-amber-500/60"
                            >
                              <KeyRound className="size-4" />
                              {isResettingSenha ? "Redefinindo..." : "Redefinir para senha padrão"}
                            </Button>
                          </div>
                        </div>
                      </>
                    )}
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
                          <option value="não resolvido">Não Resolvido</option>
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
                      <AnimatedTable
                        rows={filteredChamados}
                        onTicketClick={handleTicketClick}
                        canAssignTechnicians={authUser.nivel === "analista"}
                        technicians={tecnicos}
                        isAssigningTicketId={isAssigningTicketId}
                        assignmentFeedback={assignmentFeedback}
                        assignmentError={assignmentError}
                        technicianLoadError={tecnicosError}
                        techniciansLoading={isTecnicosLoading}
                        onAssignTechnician={handleAssignTechnician}
                        userRole={authUser.nivel}
                        isUpdatingStatusId={isUpdatingStatusId}
                        onUpdateStatus={handleUpdateStatus}
                    />
                    )}
                  </div>
                )
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
                            defaultValue="Help123@"
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
                          {authUser.nivel === "adm" ? <option value="adm">Administrador</option> : null}
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
                </>
              )}
            </motion.div>
            {canAccessCurrentSection && section === "relatorios" ? (
        <RelatoriosPage />
    ) : null}
          </AnimatePresence>
        </section>
      </div>
      
      {/* <FloatingAssistant /> */}
      <nav className="glass-panel fixed bottom-3 left-1/2 z-40 flex -translate-x-1/2 gap-2 rounded-2xl p-2 lg:hidden">
        {[
          { key: "overview", label: "Home" },
          { key: "chamados", label: "Chamados" },
          { key: "historico", label: "Histórico" },
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
