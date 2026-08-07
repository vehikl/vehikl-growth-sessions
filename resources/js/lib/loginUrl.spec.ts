import { loginUrl } from '@/lib/loginUrl';
import { describe, expect, it, vi } from 'vitest';

vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, string>) => `/${name}?${new URLSearchParams(params).toString()}`,
}));

describe('loginUrl', () => {
    it('carries the page the visitor is on, so login returns them to it', () => {
        const url = loginUrl('github', { pathname: '/', search: '?date=2020-01-15&session=42' });

        expect(new URL(url, 'https://growth.test').searchParams.get('redirect')).toBe('/?date=2020-01-15&session=42');
    });

    it('carries the bare path when there is no query string', () => {
        const url = loginUrl('github', { pathname: '/about', search: '' });

        expect(new URL(url, 'https://growth.test').searchParams.get('redirect')).toBe('/about');
    });

    it('logs in with the requested driver', () => {
        const url = loginUrl('discord', { pathname: '/', search: '' });

        expect(new URL(url, 'https://growth.test').searchParams.get('driver')).toBe('discord');
    });
});
