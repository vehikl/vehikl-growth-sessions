import { ref } from 'vue';

export type Theme = 'light' | 'dark';

const STORAGE_KEY = 'gs-theme';
const theme = ref<Theme>('light');
let initialized = false;

function systemPrefersDark(): boolean {
    return typeof window !== 'undefined' && !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function readStoredTheme(): Theme | null {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored === 'light' || stored === 'dark' ? stored : null;
    } catch {
        return null;
    }
}

function apply(next: Theme): void {
    theme.value = next;
    if (typeof document === 'undefined') return;
    const root = document.documentElement;
    root.classList.toggle('dark', next === 'dark');
    root.style.backgroundColor = next === 'dark' ? '#14161c' : '#f9fafb';
}

/**
 * Initialise the theme from storage / system preference.
 * The blade template applies the `.dark` class before hydration to avoid a flash;
 * this simply syncs the reactive ref with whatever is already on <html>.
 */
export function initTheme(): void {
    if (initialized || typeof window === 'undefined') return;
    initialized = true;

    const stored = readStoredTheme();
    const hasDarkClass = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    const resolved: Theme = stored ?? (hasDarkClass || systemPrefersDark() ? 'dark' : 'light');
    apply(resolved);
}

export function useTheme() {
    if (!initialized) {
        initTheme();
    }

    function setTheme(next: Theme): void {
        apply(next);
        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch {
            /* ignore persistence failures (private mode, etc.) */
        }
    }

    function toggleTheme(): void {
        setTheme(theme.value === 'dark' ? 'light' : 'dark');
    }

    return { theme, toggleTheme, setTheme };
}
