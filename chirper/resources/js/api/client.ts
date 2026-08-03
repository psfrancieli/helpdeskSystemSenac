const BASE_URL = (import.meta.env.VITE_API_URL as string | undefined) ?? '';

interface ApiResponse<T> {
    success: boolean;
    data: T;
    error?: string;
    message?: string;
}

export class ApiError extends Error {
    status: number;

    constructor(message: string, status: number) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
    }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<ApiResponse<T>> {
    const response = await fetch(`${BASE_URL}${path}`, {
        credentials: 'include',
        ...init,
    });

    const rawBody = await response.text();
    let body: ApiResponse<T> | null = null;

    if (rawBody) {
        try {
            body = JSON.parse(rawBody) as ApiResponse<T>;
        } catch {
            if (!response.ok) {
                throw new ApiError(`Erro ${response.status}: ${response.statusText}`, response.status);
            }

            throw new Error('Resposta inválida da API');
        }
    }

    if (!response.ok) {
        throw new ApiError(body?.error ?? body?.message ?? `Erro ${response.status}: ${response.statusText}`, response.status);
    }

    if (!body) {
        throw new Error('Resposta vazia da API');
    }

    if (!body.success) {
        throw new Error(body.error ?? body.message ?? 'Erro desconhecido na API');
    }

    return body;
}

async function get<T>(path: string): Promise<ApiResponse<T>> {
    return request<T>(path);
}

async function post<T>(path: string, data: unknown): Promise<ApiResponse<T>> {
    return request<T>(path, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });
}

export const apiClient = { get, post };
