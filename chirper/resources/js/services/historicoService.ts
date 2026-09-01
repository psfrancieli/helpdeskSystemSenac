export interface HistoryItem {
    descricao: string;
    data: string;
    id_chamado: number;
    id_usuario: number; // <- era id_usuario_tecnico, mas o backend retorna id_usuario
}

interface HistoryResponse {
    success: boolean;
    data: HistoryItem[];
    message?: string;
}

interface CreateHistoryResponse {
    success: boolean;
    message?: string;
}

export async function getHistoryByTicketId(
    ticketId: number
): Promise<HistoryItem[]> {
    // era: `/api/historico/chamado/${ticketId}` -> não bate com a rota do backend
    const response = await fetch(
        `/api/historico?id_chamado=${ticketId}`,
        {
            method: "GET",
            headers: { Accept: "application/json" },
            credentials: "include",
        }
    );

    const data: HistoryResponse = await response.json();

    if (!data.success) {
        throw new Error(data.message || "Erro ao buscar histórico");
    }

    return data.data ?? [];
}

export async function createHistoryComment(
    ticketId: number,
    descricao: string
): Promise<void> {
    const response = await fetch("/api/historico", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
            id_chamado: ticketId,
            descricao,
        }),
    });

    const data: CreateHistoryResponse = await response.json();

    if (!data.success) {
        throw new Error(data.message || "Erro ao enviar comentário");
    }
}