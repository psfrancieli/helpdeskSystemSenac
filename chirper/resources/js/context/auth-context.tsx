import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

import { ApiError } from '../api/client';
import { fetchAuthenticatedUser, loginRequest, logoutRequest } from '../services/authService';
import type { HelpdeskUser } from '../types/helpdesk';

interface LoginCredentials {
    email: string;
    password: string;
    remember: boolean;
}

interface AuthContextValue {
    user: HelpdeskUser | null;
    isAuthenticated: boolean;
    isInitializing: boolean;
    login: (credentials: LoginCredentials) => Promise<void>;
    logout: () => Promise<void>;
    refreshUser: () => Promise<void>;
}

const AUTH_STORAGE_KEY = 'helpdesk.auth.user';

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

function loadStoredUser(): HelpdeskUser | null {
    try {
        const raw = localStorage.getItem(AUTH_STORAGE_KEY);
        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as Partial<HelpdeskUser>;

        if (
            typeof parsed.id !== 'number' ||
            typeof parsed.nome !== 'string' ||
            typeof parsed.email !== 'string' ||
            typeof parsed.nivel !== 'string' ||
            typeof parsed.ativo !== 'boolean' ||
            typeof parsed.telefone !== 'string'
        ) {
            return null;
        }

        return parsed as HelpdeskUser;
    } catch {
        return null;
    }
}

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<HelpdeskUser | null>(() => loadStoredUser());
    const [isInitializing, setIsInitializing] = useState(true);

    const persistUser = useCallback((nextUser: HelpdeskUser | null, shouldRemember = true) => {
        setUser(nextUser);

        if (!nextUser || !shouldRemember) {
            localStorage.removeItem(AUTH_STORAGE_KEY);
            return;
        }

        localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(nextUser));
    }, []);

    const refreshUser = useCallback(async () => {
        try {
            const me = await fetchAuthenticatedUser();
            persistUser(me, true);
        } catch (error) {
            if (error instanceof ApiError && error.status === 401) {
                persistUser(null, false);
                return;
            }

            throw error;
        }
    }, [persistUser]);

    useEffect(() => {
        refreshUser()
            .catch(() => {
                persistUser(null, false);
            })
            .finally(() => {
                setIsInitializing(false);
            });
    }, [persistUser, refreshUser]);

    const login = useCallback(
        async ({ email, password, remember }: LoginCredentials) => {
            const authUser = await loginRequest({ email, senha: password });
            persistUser(authUser, remember);
        },
        [persistUser],
    );

    const logout = useCallback(async () => {
        try {
            await logoutRequest();
        } finally {
            persistUser(null, false);
        }
    }, [persistUser]);

    const value = useMemo<AuthContextValue>(
        () => ({
            user,
            isAuthenticated: user !== null,
            isInitializing,
            login,
            logout,
            refreshUser,
        }),
        [isInitializing, login, logout, refreshUser, user],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth deve ser usado dentro de AuthProvider');
    }

    return context;
}
