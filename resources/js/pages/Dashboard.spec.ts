import Dashboard from '@/pages/Dashboard.vue';
import { mountWithInertia } from '@/test-utils/inertia-test-helper';
import { IDashboard, IHostedSession, IMemberYetToMobWith, IMobSquadMember, IPaginated, ISessionSummary, ITagUsage } from '@/types';
import { describe, expect, test, vi } from 'vitest';

vi.mock('ziggy-js', () => ({
    route: (name: string, params?: Record<string, unknown>) => {
        if (name === 'home') return '/';
        if (name === 'dashboard') return params?.sort ? `/dashboard?sort=${params.sort}` : '/dashboard';

        return `/growth_sessions/${params?.growth_session}`;
    },
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
    series_name: 'Vue Deep Dive',
};

const paintingWithCode: IHostedSession = {
    id: 8,
    title: 'Painting With Code',
    date: '2020-01-13',
    date_label: 'Jan 13, 2020',
    is_upcoming: false,
    attendee_count: 0,
    tags: [],
    series_name: null,
};

const summary: ISessionSummary = {
    sessions_hosted_count: 2,
    sessions_attended_count: 5,
    upcoming_count: 1,
    growth_minutes_count: 90,
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

const yetToMobWith: IMemberYetToMobWith[] = [{ id: 2, name: 'Brady Deroy', avatar: 'https://example.test/brady.png' }];

const topTags: ITagUsage[] = [
    { id: 1, name: 'Vue', sessions_count: 6 },
    { id: 2, name: 'Testing', sessions_count: 2 },
];

const mobSquad: IMobSquadMember[] = [
    { id: 3, name: 'Alex Barry', avatar: 'https://example.test/alex.png', sessions_together_count: 7 },
    { id: 4, name: 'Gavin Abeele', avatar: null, sessions_together_count: 1 },
];

function mountDashboard(overrides: Partial<IPaginated<IHostedSession>> = {}, rest: Partial<IDashboard> = {}) {
    const props: IDashboard = {
        summary,
        hosted_sessions: paginator(overrides),
        sort: 'date',
        top_tags: topTags,
        mob_squad: mobSquad,
        yet_to_mob_with: yetToMobWith,
        ...rest,
    };

    return mountWithInertia(Dashboard, { props });
}

describe('Dashboard', () => {
    test('renders the session summary', () => {
        const wrapper = mountDashboard(
            {},
            { summary: { sessions_hosted_count: 3, sessions_attended_count: 7, upcoming_count: 1, growth_minutes_count: 11610 } },
        );

        const tiles = wrapper.findAll('section[aria-label="Session summary"] article');
        const readTile = (tile: (typeof tiles)[number]) => `${tile.get('strong').text()} ${tile.get('span').text()}`;

        expect(tiles).toHaveLength(4);
        expect(readTile(tiles[0])).toBe('3 Sessions hosted');
        expect(readTile(tiles[1])).toBe('7 Sessions attended');
        expect(readTile(tiles[2])).toBe('1 Upcoming');
        expect(readTile(tiles[3])).toBe('193h 30m Time of growth');
    });

    test('offers a sort control per orderable field', () => {
        const wrapper = mountDashboard();

        expect(wrapper.find('[data-testid="sort-date"]').attributes('href')).toBe('/dashboard?sort=date');
        expect(wrapper.find('[data-testid="sort-name"]').attributes('href')).toBe('/dashboard?sort=name');
        expect(wrapper.find('[data-testid="sort-attendees"]').attributes('href')).toBe('/dashboard?sort=attendees');
    });

    test('marks the sort the list came back in as the current one', () => {
        const wrapper = mountDashboard({}, { sort: 'attendees' });

        expect(wrapper.find('[data-testid="sort-attendees"]').attributes('aria-current')).toBe('true');
        expect(wrapper.find('[data-testid="sort-date"]').attributes('aria-current')).toBeUndefined();
        expect(wrapper.find('[data-testid="sort-name"]').attributes('aria-current')).toBeUndefined();
    });

    test('picks the active sort out by colour, not just by weight', () => {
        const wrapper = mountDashboard({}, { sort: 'name' });

        expect(wrapper.find('[data-testid="sort-name"]').classes()).toContain('gs-text-input');
        expect(wrapper.find('[data-testid="sort-date"]').classes()).not.toContain('gs-text-input');
        expect(wrapper.find('[data-testid="sort-attendees"]').classes()).not.toContain('gs-text-input');
    });

    test('sits the sort control in the hosted sessions heading rather than above the rows', () => {
        const heading = mountDashboard().find('#hosted-sessions-heading');
        const header = heading.element.parentElement;

        expect(header?.querySelector('[data-testid="sort-date"]')).not.toBeNull();
    });

    test('labels the sorts by field alone, with no separate caption', () => {
        const control = mountDashboard().find('nav[aria-label="Sort sessions"]');

        expect(control.exists()).toBe(true);

        const labels = control.findAll('a').map((link) => link.text());

        expect(labels).toEqual(['Date', 'Name', 'Attendees']);
        expect(control.text()).toBe(labels.join(''));
    });

    test('hides the sort controls when there is nothing to sort', () => {
        const wrapper = mountDashboard({ data: [], from: null, to: null, total: 0 });

        expect(wrapper.find('[data-testid="sort-date"]').exists()).toBe(false);
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

    test('renders the title as plain text, not as a link', () => {
        const row = mountDashboard().findAll('ul li')[0];

        expect(row.text()).toContain('Vue Testing Deep Dive');
        expect(row.find('a').exists()).toBe(false);
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

        expect(mainColumn.find('section[aria-label="Session summary"]').exists()).toBe(true);
        expect(mainColumn.find('section[aria-labelledby="hosted-sessions-heading"]').exists()).toBe(true);
        expect(mainColumn.find('[data-testid="dashboard-sidebar"]').exists()).toBe(false);
    });

    test('orders the sidebar after the sessions column so it stacks underneath when the columns collapse', () => {
        const columns = mountDashboard().find('[data-testid="dashboard-columns"]');
        const testIds = columns.element.children ? [...columns.element.children].map((child) => child.getAttribute('data-testid')) : [];

        expect(testIds).toEqual(['dashboard-main-column', 'dashboard-sidebar']);
    });

    test('renders the mob squad with each member’s shared session count', () => {
        const rows = mountDashboard().findAll('[data-testid="mob-squad-member"]');

        expect(rows).toHaveLength(2);
        expect(rows[0].text()).toContain('Alex Barry');
        expect(rows[0].text()).toContain('7 mobs');
        expect(rows[1].text()).toContain('Gavin Abeele');
        expect(rows[1].text()).toContain('1 mob');
    });

    test('puts the mob squad at the top of the sidebar, above the top tags', () => {
        const sidebar = mountDashboard().find('[data-testid="dashboard-sidebar"]');

        expect(sidebar.find('[data-testid="mob-squad-member"]').exists()).toBe(true);
        expect(sidebar.text().indexOf('Mob Squad')).toBeLessThan(sidebar.text().indexOf('Top tags'));
    });

    test('explains what the Mob Squad is on demand, since “top five collaborators” is not obvious from the title', async () => {
        const card = mountDashboard().find('[aria-labelledby="mob-squad-heading"]');

        await card.find('[data-testid="info-popover-trigger"]').trigger('click');

        expect(card.find('[data-testid="info-popover-panel"]').text()).toContain('five people');
    });

    test('hides the whole Mob Squad card when the user has never mobbed with anybody', () => {
        const wrapper = mountDashboard({}, { mob_squad: [] });

        expect(wrapper.find('[aria-labelledby="mob-squad-heading"]').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Mob Squad');
    });

    test('leaves the rest of the sidebar in place when the Mob Squad card is hidden', () => {
        const sidebar = mountDashboard({}, { mob_squad: [] }).find('[data-testid="dashboard-sidebar"]');

        expect(sidebar.text()).toContain('Top tags');
        expect(sidebar.text()).toContain('Yet to mob with');
    });

    test('renders the top tags with their usage counts, in the order given', () => {
        const entries = mountDashboard().findAll('[data-testid="tag-usage"]');

        expect(entries).toHaveLength(2);
        expect(entries[0].text()).toContain('Vue');
        expect(entries[0].text()).toContain('6');
        expect(entries[1].text()).toContain('Testing');
        expect(entries[1].text()).toContain('2');
    });

    test('puts the top tags in the sidebar, above the people left to mob with', () => {
        const sidebar = mountDashboard().find('[data-testid="dashboard-sidebar"]');

        expect(sidebar.find('[data-testid="tag-usage"]').exists()).toBe(true);
        expect(sidebar.text().indexOf('Top tags')).toBeLessThan(sidebar.text().indexOf('Yet to mob with'));
    });

    test('hides the whole Top tags card when the user has never tagged a session', () => {
        const wrapper = mountDashboard({}, { top_tags: [] });

        expect(wrapper.find('[aria-labelledby="top-tags-heading"]').exists()).toBe(false);
        expect(wrapper.findAll('[data-testid="tag-usage"]')).toHaveLength(0);
        expect(wrapper.text()).not.toContain('Top tags');
    });

    test('leaves the rest of the sidebar in place when the Top tags card is hidden', () => {
        const sidebar = mountDashboard({}, { top_tags: [] }).find('[data-testid="dashboard-sidebar"]');

        expect(sidebar.text()).toContain('Mob Squad');
        expect(sidebar.text()).toContain('Yet to mob with');
    });

    /** Fewer than five is still a ranking worth reading — only zero earns the card its silence. */
    test('still shows the Top tags card when the user has only tagged one thing', () => {
        const wrapper = mountDashboard({}, { top_tags: [{ id: 1, name: 'Vue', sessions_count: 6 }] });

        expect(wrapper.find('[aria-labelledby="top-tags-heading"]').exists()).toBe(true);
        expect(wrapper.findAll('[data-testid="tag-usage"]')).toHaveLength(1);
    });

    test('renders the members the user has yet to mob with', () => {
        const wrapper = mountDashboard();

        expect(wrapper.text()).toContain('Yet to mob with');
        expect(wrapper.text()).toContain('Brady Deroy');
    });

    test('shows the photo of a member yet to be mobbed with', () => {
        const avatar = mountDashboard().find('[data-testid="yet-to-mob-avatar"]');

        expect(avatar.find('img').attributes('src')).toBe('https://example.test/brady.png');
    });

    test('falls back to initials for a member yet to be mobbed with who has no photo', () => {
        const wrapper = mountDashboard({}, { yet_to_mob_with: [{ id: 2, name: 'Brady Deroy', avatar: null }] });
        const avatar = wrapper.find('[data-testid="yet-to-mob-avatar"]');

        expect(avatar.find('img').exists()).toBe(false);
        expect(avatar.text()).toBe('BD');
    });

    test('shows an empty state when there is nobody left to mob with', () => {
        const wrapper = mountDashboard({}, { yet_to_mob_with: [] });

        expect(wrapper.text()).toContain("You're all caught up!");
    });
});
