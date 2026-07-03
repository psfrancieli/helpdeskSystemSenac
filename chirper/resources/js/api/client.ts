const BASE_URL = (import.meta.env.VITE_API_URL as string | undefined) ?? '';

interface ApiResponse<T> {
    success: boolean;
    data: T;
    error?: string;
}

async function get<T>(path: string): Promise<ApiResponse<T>> {
    const response = await fetch(`${BASE_URL}${path}`);

    if (!response.ok) {
        throw new Error(`Erro ${response.status}: ${response.statusText}`);
    }

    const body = (await response.json()) as ApiResponse<T>;

    if (!body.success) {
        throw new Error(body.error ?? 'Erro desconhecido na API');
    }

    return body;
}

export const apiClient = { get };
