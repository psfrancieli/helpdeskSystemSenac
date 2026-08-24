import type {
    DashboardMetric,
    HelpdeskCategory,
    HelpdeskStatus,
    HelpdeskTicket,
    HelpdeskUser,
} from '../types/helpdesk';

export const currentUser: HelpdeskUser = {
    id: 1,
    nome: 'Marina Rubim',
    email: 'marina@octopus.system',
    nivel: 'adm',
    ativo: true,
    telefone: '(15) 99854-2782',
};

export const users: HelpdeskUser[] = [
    currentUser,
    { id: 2, nome: 'Leonardo Costa', email: 'leo@octopus.system', nivel: 'analista', ativo: true, telefone: '(15) 99854-2782' },
    { id: 3, nome: 'Bruna Matos', email: 'bruna@octopus.system', nivel: 'tecnico', ativo: true, telefone: '(15) 99854-2781' },
    { id: 4, nome: 'Daniel Lima', email: 'daniel@octopus.system', nivel: 'usuario', ativo: true, telefone: '(15) 99854-2780' },
];

export const categories: HelpdeskCategory[] = [
    { id: 1, nome: 'Infraestrutura' },
    { id: 2, nome: 'Hardware' },
    { id: 3, nome: 'Sistema' },
    { id: 4, nome: 'Acesso' },
];

export const statuses: HelpdeskStatus[] = [
    { id: 1, nome: 'pendente', ativo: true },
    { id: 2, nome: 'concluido', ativo: true },
    { id: 3, nome: 'cancelado', ativo: true },
];

export const tickets: HelpdeskTicket[] = [
    {
        id: 1021,
        titulo: 'Notebook sem acesso à VPN',
        patrimonio: 'PATR-9090',
        prioridade: 'alta',
        categoria: 'Infraestrutura',
        solicitante: 'Daniel Lima',
        responsavel: 'Bruna Matos',
        status: 'pendente',
    },
    {
        id: 1022,
        titulo: 'Sistema de chamados lento no horário de pico',
        patrimonio: 'PATR-0101',
        prioridade: 'muito alta',
        categoria: 'Sistema',
        solicitante: 'Leonardo Costa',
        responsavel: 'Marina Rubim',
        status: 'pendente',
    },
    {
        id: 1023,
        titulo: 'Troca de teclado mecânico',
        patrimonio: 'PATR-8842',
        prioridade: 'baixa',
        categoria: 'Hardware',
        solicitante: 'Bruna Matos',
        responsavel: 'Daniel Lima',
        status: 'concluido',
    },
];

export const metrics: DashboardMetric[] = [
    {
        key: 'total-users',
        title: 'Usuários ativos',
        value: `${users.filter((user) => user.ativo).length}`,
        growth: 8.2,
        trend: [24, 28, 30, 27, 32, 35, 38],
    },
    {
        key: 'open-tickets',
        title: 'Chamados pendentes',
        value: `${tickets.filter((ticket) => ticket.status === 'pendente').length}`,
        growth: 4.6,
        trend: [12, 10, 15, 14, 16, 17, 18],
    },
    {
        key: 'closed-tickets',
        title: 'Chamados concluídos',
        value: `${tickets.filter((ticket) => ticket.status === 'concluido').length}`,
        growth: 12.4,
        trend: [5, 7, 8, 10, 11, 13, 14],
    },
    {
        key: 'response-time',
        title: 'Tempo médio',
        value: '1h 24m',
        growth: -6.1,
        trend: [130, 124, 118, 112, 106, 95, 84],
    },
];

