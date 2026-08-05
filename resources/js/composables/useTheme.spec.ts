import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

function stubMatchMedia(prefersDark: boolean) {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        configurable: true,
        value: (query: string) => ({
            matches: prefersDark,
            media: query,
            onchange: null,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            addListener: vi.fn(),
            removeListener: vi.fn(),
            dispatchEvent: vi.fn(),
        }),
    });
}

// The composable keeps module-level singleton state, so reset the module registry between tests.
async function loadTheme() {
    return import('@/composables/useTheme');
}

describe('useTheme', () => {
    beforeEach(() => {
        vi.resetModules();
        localStorage.clear();
        document.documentElement.className = '';
        document.documentElement.style.backgroundColor = '';
        stubMatchMedia(false);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('defaults to light when nothing is stored and the system prefers light', async () => {
        const { useTheme } = await loadTheme();

        expect(useTheme().theme.value).toBe('light');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('adopts a stored preference over the system setting', async () => {
        localStorage.setItem('gs-theme', 'dark');
        stubMatchMedia(false);

        const { useTheme } = await loadTheme();

        expect(useTheme().theme.value).toBe('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('falls back to the system preference when nothing is stored', async () => {
        stubMatchMedia(true);

        const { useTheme } = await loadTheme();

        expect(useTheme().theme.value).toBe('dark');
    });

    it('respects a .dark class already applied by the blade template', async () => {
        document.documentElement.classList.add('dark');
        stubMatchMedia(false);

        const { useTheme } = await loadTheme();

        expect(useTheme().theme.value).toBe('dark');
    });

    it('setTheme applies the class, background and persists the choice', async () => {
        const { useTheme } = await loadTheme();
        const { setTheme, theme } = useTheme();

        setTheme('dark');

        expect(theme.value).toBe('dark');
        expect(localStorage.getItem('gs-theme')).toBe('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(document.documentElement.style.backgroundColor).toBeTruthy();
    });

    it('toggleTheme flips between light and dark', async () => {
        const { useTheme } = await loadTheme();
        const { toggleTheme, theme } = useTheme();

        expect(theme.value).toBe('light');
        toggleTheme();
        expect(theme.value).toBe('dark');
        toggleTheme();
        expect(theme.value).toBe('light');
    });

    it('still applies the theme when persistence throws (private mode)', async () => {
        const { useTheme } = await loadTheme();
        const { setTheme } = useTheme();
        const spy = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('denied');
        });

        expect(() => setTheme('dark')).not.toThrow();
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        spy.mockRestore();
    });
});
