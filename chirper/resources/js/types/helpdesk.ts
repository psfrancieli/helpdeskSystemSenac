export type UserRole = 'adm' | 'analista' | 'tecnico' | 'usuario';
export type TicketPriority = 'baixa' | 'media' | 'alta' | 'muito alta';
export type TicketStatus = 'pendente' | 'cancelado' | 'concluido';
export type DashboardSection = 'overview' | 'usuarios' | 'chamados' | 'categorias' | 'historico' | 'status' | 'criarChamado' | 'criarUsuario';

export interface HelpdeskUser {
    id: number;
    nome: string;
    email: string;
    nivel: UserRole;
    ativo: boolean;
}

export interface CreateHelpdeskUser {
    nome: string;
    email: string;
    senha: string;
    nivel: UserRole;
    ativo: boolean;
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
    status: TicketStatus;
}

export interface DashboardMetric {
    key: string;
    title: string;
    value: string;
    growth: number;
    trend: number[];
}

