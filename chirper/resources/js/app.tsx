import '../css/app.css';

import { AnimatePresence, motion } from 'framer-motion';
import { createRoot } from 'react-dom/client';
import { HashRouter, Navigate, Route, Routes, useNavigate } from 'react-router-dom';

import { AuthProvider, useAuth } from './context/auth-context';
import { ThemeProvider } from './context/theme-context';
import { DashboardPage } from '@/pages/dashboard-page';
import { LoginPage } from '@/pages/login-page';

function AppShell() {
    const { isAuthenticated, isInitializing, login, logout } = useAuth();
    const navigate = useNavigate();

    async function handleLogin(credentials: { email: string; password: string; remember: boolean }) {
        await login(credentials);
        navigate('/dashboard');
    }

    async function handleLogout() {
        await logout();
        navigate('/login');
    }

    if (isInitializing) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-stone-950 text-stone-200">
                Carregando sessão...
            </main>
        );
    }

    return (
        <AnimatePresence mode="wait">
            <motion.div
                key={isAuthenticated ? 'auth' : 'guest'}
                initial={{ opacity: 0, scale: 0.99, filter: 'blur(6px)' }}
                animate={{ opacity: 1, scale: 1, filter: 'blur(0px)' }}
                exit={{ opacity: 0, scale: 1.01, filter: 'blur(6px)' }}
                transition={{ duration: 0.45, ease: 'easeOut' }}
            >
                <Routes>
                    <Route path="/login" element={isAuthenticated ? <Navigate to="/dashboard" replace /> : <LoginPage onLogin={handleLogin} />} />
                    <Route path="/dashboard/:section?" element={isAuthenticated ? <DashboardPage onLogout={handleLogout} /> : <Navigate to="/login" replace />} />
                    <Route path="*" element={<Navigate to={isAuthenticated ? '/dashboard' : '/login'} replace />} />
                </Routes>
            </motion.div>
        </AnimatePresence>
    );
}

function App() {
    return (
        <HashRouter>
            <ThemeProvider>
                <AuthProvider>
                    <AppShell />
                </AuthProvider>
            </ThemeProvider>
        </HashRouter>
    );
}

const container = document.getElementById('app');

if (container) {
    createRoot(container).render(<App />);
}
