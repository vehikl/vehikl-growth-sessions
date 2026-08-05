import Dashboard from '@/pages/Dashboard.vue';
import { mountWithInertia } from '@/test-utils/inertia-test-helper';
import { IDashboard, IHostedSession, IPaginated } from '@/types';
import { describe, expect, test, vi } from 'vitest';

vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) => (name === 'home' ? '/' : `/growth_sessions/${params?.growth_session}`),
}));

const vueTesting: IHostedSession = {
    id: 7,
    title: 'Vue Testing Deep Dive',
    date: '2020-01-15',
    date_label: 'Wed, Jan 15, 2020',
    time_label: '3:30 pm – 5:00 pm',
    attendee_count: 4,
    tags: [
        { id: 1, name: 'Vue' },
        { id: 2, name: 'Testing' },
    ],
};

const paintingWithCode: IHostedSession = {
    id: 8,
    title: 'Painting With Code',
    date: '2020-01-13',
    date_label: 'Mon, Jan 13, 2020',
    time_label: '10:00 am – 11:00 am',
    attendee_count: 0,
    tags: [],
};

function paginator(overrides: Partial<IPaginated<IHostedSession>> = {}): IPaginated<IHostedSession> {
    return {
        data: [vueTesting, paintingWithCode],
        current_page: 1,
        last_page: 1,
        from: 1,
        to: 2,
        total: 2,
        prev_page_url: null,
        next_page_url: null,
        ...overrides,
    };
}

function mountDashboard(overrides: Partial<IPaginated<IHostedSession>> = {}) {
    const props: IDashboard = { hosted_sessions: paginator(overrides) };

    return mountWithInertia(Dashboard, { props });
}

describe('Dashboard', () => {
    test('renders one row per hosted session', () => {
        const wrapper = mountDashboard();

        expect(wrapper.findAll('tbody tr')).toHaveLength(2);
    });

    test('renders each session’s date, time, title, attendee count and tags', () => {
        const rows = mountDashboard().findAll('tbody tr');

        const [date, time, title, attendees, tags] = rows[0].findAll('td').map((cell) => cell.text());
        expect(date).toBe('Wed, Jan 15, 2020');
        expect(time).toBe('3:30 pm – 5:00 pm');
        expect(title).toBe('Vue Testing Deep Dive');
        expect(attendees).toBe('4');
        expect(tags).toContain('Vue');
        expect(tags).toContain('Testing');

        const secondRow = rows[1].findAll('td').map((cell) => cell.text());
        expect(secondRow[0]).toBe('Mon, Jan 13, 2020');
        expect(secondRow[2]).toBe('Painting With Code');
        expect(secondRow[3]).toBe('0');
    });

    test('renders the title as an anchor pointing at that session', () => {
        const link = mountDashboard().findAll('tbody tr')[0].find('a');

        expect(link.text()).toBe('Vue Testing Deep Dive');
        expect(link.attributes('href')).toBe('/growth_sessions/7');
    });

    test('shows an explanation pointing at the Board when nothing has been hosted', () => {
        const wrapper = mountDashboard({ data: [], from: null, to: null, total: 0 });

        expect(wrapper.find('tbody').exists()).toBe(false);
        expect(wrapper.text()).toContain("You haven't hosted any Growth Sessions yet.");
        expect(wrapper.find('[data-testid="empty-board-link"]').attributes('href')).toBe('/');
    });

    test('summarises which rows are shown out of the total', () => {
        const wrapper = mountDashboard({ from: 16, to: 30, total: 47, current_page: 2, last_page: 4 });

        expect(wrapper.text()).toContain('Showing 16–30 of 47');
    });

    test('disables Previous on the first page', () => {
        const wrapper = mountDashboard({ current_page: 1, last_page: 3, prev_page_url: null, next_page_url: '/dashboard?page=2' });

        expect(wrapper.find('[data-testid="previous-page"]').attributes('disabled')).toBeDefined();
        expect(wrapper.find('[data-testid="next-page"]').attributes('disabled')).toBeUndefined();
    });

    test('disables Next on the last page', () => {
        const wrapper = mountDashboard({ current_page: 3, last_page: 3, prev_page_url: '/dashboard?page=2', next_page_url: null });

        expect(wrapper.find('[data-testid="previous-page"]').attributes('disabled')).toBeUndefined();
        expect(wrapper.find('[data-testid="next-page"]').attributes('disabled')).toBeDefined();
    });
});
