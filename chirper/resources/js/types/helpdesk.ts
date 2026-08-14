export type UserRole = 'adm' | 'analista' | 'tecnico' | 'usuario';
export type TicketPriority = 'baixa' | 'media' | 'alta' | 'muito alta';
export type TicketStatus = 'pendente' | 'cancelado' | 'concluido';
export type DashboardSection = 'overview' | 'usuarios' | 'chamados' | 'historico' | 'status' | 'criarChamado' | 'criarUsuario' | 'perfil';
export type NotificationType = 'novo' | 'atribuido' | 'resolvido' | 'cancelado' | 'atualizado';

export interface HelpdeskUser {
    id: number;
    nome: string;
    email: string;
    nivel: UserRole;
    ativo: boolean;
    telefone: string;
}

export interface CreateHelpdeskUser {
    nome: string;
    email: string;
    senha: string;
    nivel: UserRole;
    ativo: boolean;
}

export interface CreateApiUserInput {
    nome: string;
    email: string;
    senha: string;
    cpf: string;
    telefone: string;
    nivel: UserRole;
}

export interface HelpdeskCategory {
    id: number;
    nome: string;
}

export interface HelpdeskStatus {
    id: number;
    nome: TicketStatus;
    ativo: boolean;
}

export interface HelpdeskTicket {
    id: number;
    titulo: string;
    patrimonio: string;
    prioridade: TicketPriority;
    categoria: string;
    solicitante: string;
    responsavel?: string;
    tecnicoId?: number | null;
    status: TicketStatus;
}

export interface CreateChamadoInput {
    titulo: string;
    descricao: string;
    prioridade: TicketPriority;
    patrimonio: string;
    id_categoria: number;
    id_usuario: number;
    id_responsavel: number | null;
    status: TicketStatus;
    data_abertura?: string;
    data_encerramento?: string | null;
}

export interface DashboardMetric {
    key: string;
    title: string;
    value: string;
    growth: number;
    trend: number[];
}

export interface HelpdeskNotification {
    id: string;
    type: NotificationType;
    title: string;
    detail: string;
    ticketId: number;
    timestamp: string;
    read: boolean;
}

