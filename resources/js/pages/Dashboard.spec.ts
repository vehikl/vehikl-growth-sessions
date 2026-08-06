import Dashboard from '@/pages/Dashboard.vue';
import { mountWithInertia } from '@/test-utils/inertia-test-helper';
import { IDashboard, IHostedSession, IHostingSummary, IMemberYetToMobWith, IPaginated, ITagUsage } from '@/types';
import { describe, expect, test, vi } from 'vitest';

vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) => (name === 'home' ? '/' : `/growth_sessions/${params?.growth_session}`),
}));

const vueTesting: IHostedSession = {
    id: 7,
    title: 'Vue Testing Deep Dive',
    date: '2020-01-15',
    date_label: 'Jan 15, 2020',
    is_upcoming: true,
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
    date_label: 'Jan 13, 2020',
    is_upcoming: false,
    attendee_count: 0,
    tags: [],
};

const summary: IHostingSummary = {
    sessions_hosted_count: 2,
    upcoming_count: 1,
    total_attendees_count: 4,
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

const yetToMobWith: IMemberYetToMobWith[] = [{ id: 2, name: 'Brady Deroy' }];

const topTags: ITagUsage[] = [
    { id: 1, name: 'Vue', sessions_count: 6 },
    { id: 2, name: 'Testing', sessions_count: 2 },
];

function mountDashboard(overrides: Partial<IPaginated<IHostedSession>> = {}, rest: Partial<IDashboard> = {}) {
    const props: IDashboard = {
        summary,
        hosted_sessions: paginator(overrides),
        top_tags: topTags,
        yet_to_mob_with: yetToMobWith,
        ...rest,
    };

    return mountWithInertia(Dashboard, { props });
}

describe('Dashboard', () => {
    test('renders the hosting summary', () => {
        const wrapper = mountDashboard({}, { summary: { sessions_hosted_count: 3, upcoming_count: 1, total_attendees_count: 10 } });

        expect(wrapper.text()).toContain('Sessions hosted');
        expect(wrapper.text()).toContain('Upcoming');
        expect(wrapper.text()).toContain('Total attendees');

        const tiles = wrapper.findAll('section[aria-label="Hosting summary"] article');
        expect(tiles[0].text()).toContain('3');
        expect(tiles[1].text()).toContain('1');
        expect(tiles[2].text()).toContain('10');
    });

    test('renders one row per hosted session', () => {
        const wrapper = mountDashboard();

        expect(wrapper.findAll('ul li')).toHaveLength(2);
    });

    test('renders each session’s date, title, attendee count and tags', () => {
        const rows = mountDashboard().findAll('ul li');

        expect(rows[0].text()).toContain('Jan 15, 2020');
        expect(rows[0].text()).toContain('Vue Testing Deep Dive');
        expect(rows[0].text()).toContain('Vue');
        expect(rows[0].text()).toContain('Testing');
        expect(rows[0].find('strong').text()).toBe('4');

        expect(rows[1].text()).toContain('Jan 13, 2020');
        expect(rows[1].text()).toContain('Painting With Code');
        expect(rows[1].find('strong').text()).toBe('0');
    });

    test('marks an upcoming session apart from a finished one', () => {
        const rows = mountDashboard().findAll('ul li');

        expect(rows[0].text()).toContain('Upcoming');
        expect(rows[1].text()).toContain('Finished');
    });

    test('renders the title as an anchor pointing at that session', () => {
        const link = mountDashboard().findAll('ul li')[0].find('a');

        expect(link.text()).toBe('Vue Testing Deep Dive');
        expect(link.attributes('href')).toBe('/growth_sessions/7');
    });

    test('shows an explanation pointing at the Board when nothing has been hosted', () => {
        const wrapper = mountDashboard({ data: [], from: null, to: null, total: 0 });

        expect(wrapper.find('ul').exists()).toBe(false);
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

    test('groups the summary and the hosted sessions into one column, leaving the sidebar its own', () => {
        const wrapper = mountDashboard();
        const mainColumn = wrapper.find('[data-testid="dashboard-main-column"]');

        expect(mainColumn.find('section[aria-label="Hosting summary"]').exists()).toBe(true);
        expect(mainColumn.find('section[aria-labelledby="hosted-sessions-heading"]').exists()).toBe(true);
        // "Yet to mob with" is the sidebar itself, so it must not be nested in the left column.
        expect(mainColumn.find('[data-testid="dashboard-sidebar"]').exists()).toBe(false);
    });

    test('orders the sidebar after the sessions column so it stacks underneath when the columns collapse', () => {
        const columns = mountDashboard().find('[data-testid="dashboard-columns"]');
        const testIds = columns.element.children ? [...columns.element.children].map((child) => child.getAttribute('data-testid')) : [];

        expect(testIds).toEqual(['dashboard-main-column', 'dashboard-sidebar']);
    });

    test('renders the top tags with their usage counts, in the order given', () => {
        const entries = mountDashboard().findAll('[data-testid="top-tag"]');

        expect(entries).toHaveLength(2);
        expect(entries[0].text()).toContain('Vue');
        expect(entries[0].text()).toContain('6');
        expect(entries[1].text()).toContain('Testing');
        expect(entries[1].text()).toContain('2');
    });

    test('puts the top tags in the sidebar, above the people left to mob with', () => {
        const sidebar = mountDashboard().find('[data-testid="dashboard-sidebar"]');

        expect(sidebar.find('[data-testid="top-tag"]').exists()).toBe(true);
        expect(sidebar.text().indexOf('Top tags')).toBeLessThan(sidebar.text().indexOf('Yet to mob with'));
    });

    test('shows an empty state when the user has never tagged a session', () => {
        const wrapper = mountDashboard({}, { top_tags: [] });

        expect(wrapper.findAll('[data-testid="top-tag"]')).toHaveLength(0);
        expect(wrapper.text()).toContain('No tags yet.');
    });

    test('renders the members the user has yet to mob with', () => {
        const wrapper = mountDashboard();

        expect(wrapper.text()).toContain('Yet to mob with');
        expect(wrapper.text()).toContain('Brady Deroy');
    });

    test('shows an empty state when there is nobody left to mob with', () => {
        const wrapper = mountDashboard({}, { yet_to_mob_with: [] });

        expect(wrapper.text()).toContain("You're all caught up!");
    });
});
