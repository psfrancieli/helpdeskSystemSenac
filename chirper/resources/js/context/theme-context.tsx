import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';

export type Theme = 'dark' | 'light';

interface ThemeContextValue {
    theme: Theme;
    toggleTheme: () => void;
}

const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

function getInitialTheme(): Theme {
    if (typeof window === 'undefined') return 'dark';

    const stored = window.localStorage.getItem('helpdesk-theme');
    return stored === 'light' ? 'light' : 'dark';
}

export function ThemeProvider({ children }: { children: ReactNode }) {
    const [theme, setTheme] = useState<Theme>(getInitialTheme);

    useEffect(() => {
        const root = document.documentElement;
        root.dataset.theme = theme;
        root.style.colorScheme = theme;
        window.localStorage.setItem('helpdesk-theme', theme);
    }, [theme]);

    function toggleTheme() {
        setTheme((current) => (current === 'dark' ? 'light' : 'dark'));
    }

    return (
        <ThemeContext.Provider value={{ theme, toggleTheme }}>
            {children}
        </ThemeContext.Provider>
    );
}

export function useTheme() {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useTheme deve ser usado dentro de ThemeProvider');
    }

    return context;
}
