import { AnimatePresence, motion } from 'framer-motion';
import { Eye, EyeOff, KeyRound, Mail } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { FloatingBackground } from '@/components/fx/floating-background';
import { OctopusMascot } from '@/components/mascot/octopus-mascot';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

interface LoginPageProps {
    onLogin: (credentials: { email: string; password: string; remember: boolean }) => Promise<void>;
}

export function LoginPage({ onLogin }: LoginPageProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [remember, setRemember] = useState(true);
    const [form, setForm] = useState({ email: '', password: '' });
    const [isLoading, setIsLoading] = useState(false);
    const [authError, setAuthError] = useState<string | null>(null);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsLoading(true);
        setAuthError(null);

        try {
            await onLogin({ ...form, remember });
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Não foi possível autenticar.';
            setAuthError(message);
        }

        setIsLoading(false);

        if (!remember) {
            setForm((prev) => ({ ...prev, password: '' }));
        }
    }

    return (
        <main className="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-8">
            <FloatingBackground />
            <div className="relative z-10 grid w-full max-w-6xl gap-6 lg:grid-cols-2">
                <motion.section
                    initial={{ opacity: 0, x: -22, filter: 'blur(10px)' }}
                    animate={{ opacity: 1, x: 0, filter: 'blur(0px)' }}
                    transition={{ duration: 0.7, ease: 'easeOut' }}
                    className="glass-panel flex flex-col items-center justify-center rounded-3xl p-8 text-center"
                >
                    <OctopusMascot className="h-56 w-56" interactive />
                    <h1 className="mt-5 text-4xl font-semibold tracking-tight text-white">Bem-vindo ao Octopus System</h1>
                    <p className="mt-3 max-w-md text-stone-300">
                        Controle tudo através de uma experiência inteligente.
                    </p>
                </motion.section>
                <motion.section
                    initial={{ opacity: 0, x: 22, filter: 'blur(10px)' }}
                    animate={{ opacity: 1, x: 0, filter: 'blur(0px)' }}
                    transition={{ duration: 0.7, ease: 'easeOut' }}
                    className="flex items-stretch"
                >
                    <Card className="w-full rounded-3xl p-6 sm:p-8">
                        <CardHeader>
                            <CardTitle className="text-2xl">Entrar</CardTitle>
                            <CardDescription>Acesse seu painel.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-4" onSubmit={handleSubmit}>
                                {authError ? (
                                    <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                                        {authError}
                                    </div>
                                ) : null}
                                <label className="block space-y-2">
                                    <span className="text-sm text-stone-200">E-mail</span>
                                    <div className="relative">
                                        <Mail className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                                        <Input
                                            className="pl-9"
                                            type="email"
                                            placeholder="voce@empresa.com"
                                            value={form.email}
                                            onChange={(event) => setForm((prev) => ({ ...prev, email: event.target.value }))}
                                            required
                                        />
                                    </div>
                                </label>
                                <label className="block space-y-2">
                                    <span className="text-sm text-stone-200">Senha</span>
                                    <div className="relative">
                                        <KeyRound className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                                        <Input
                                            className="pl-9 pr-10"
                                            type={showPassword ? 'text' : 'password'}
                                            placeholder="••••••••"
                                            value={form.password}
                                            onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
                                            required
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword((current) => !current)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-stone-300 hover:text-white"
                                        >
                                            {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                        </button>
                                    </div>
                                </label>
                                <AnimatePresence mode="wait">
                                    <motion.button
                                        key={isLoading ? 'loading' : 'ready'}
                                        type="submit"
                                        initial={{ opacity: 0, y: 6 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        exit={{ opacity: 0, y: -6 }}
                                        whileHover={{ scale: 1.01 }}
                                        className="relative flex h-11 w-full items-center justify-center overflow-hidden rounded-xl bg-gradient-to-r from-amber-600 via-amber-500 to-orange-400 font-medium text-white shadow-[0_10px_35px_rgba(217,119,6,0.30)] disabled:opacity-60"
                                        disabled={isLoading}
                                    >
                                        <motion.span
                                            className="absolute inset-0 bg-gradient-to-r from-transparent via-white/25 to-transparent"
                                            animate={{ x: ['-100%', '100%'] }}
                                            transition={{ duration: 1.6, repeat: Number.POSITIVE_INFINITY, ease: 'linear' }}
                                        />
                                        <span className="relative z-10">{isLoading ? 'Entrando...' : 'Entrar'}</span>
                                    </motion.button>
                                </AnimatePresence>
                            </form>
                        </CardContent>
                    </Card>
                </motion.section>
            </div>
        </main>
    );
}

