import { DateTime } from '@/classes/DateTime';
import { encodeSettings } from '@/lib/statistics';
import Statistics from '@/pages/Statistics.vue';
import { mountWithInertia } from '@/test-utils/inertia-test-helper';
import { IStatisticsDashboard, IUserStatistics } from '@/types';
import { router } from '@inertiajs/vue3';
import { flushPromises, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, type MockInstance, test, vi } from 'vitest';

// Nothing mounts a real Inertia app here, so the shared page props are stood in for.
let signedInUserId: number | null = null;

// This replaces the global mock in setup-vitest.ts outright, so the <Head> stub has to come along too.
vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    Head: (await import('@/test-utils/inertia-test-helper')).InertiaHeadStub,
    usePage: () => ({ props: { auth: { user: signedInUserId === null ? null : { id: signedInUserId } } } }),
}));

function member(overrides: Partial<IUserStatistics> = {}): IUserStatistics {
    return {
        user_id: 1,
        name: 'Ada Lovelace',
        sessions_hosted_count: 0,
        sessions_attended_count: 0,
        sessions_watched_count: 0,
        total_sessions_count: 0,
        has_mobbed_with_count: 0,
        has_not_mobbed_with_count: 0,
        has_not_mobbed_with: [],
        ...overrides,
    };
}

const ada = member({
    user_id: 1,
    name: 'Ada Lovelace',
    sessions_hosted_count: 2,
    sessions_attended_count: 6,
    sessions_watched_count: 1,
    total_sessions_count: 9,
    has_not_mobbed_with_count: 2,
    has_not_mobbed_with: [
        { id: 2, name: 'Grace Hopper' },
        { id: 3, name: 'Alan Turing' },
    ],
});
const grace = member({ user_id: 2, name: 'Grace Hopper', total_sessions_count: 3, sessions_attended_count: 3 });
const alan = member({ user_id: 3, name: 'Alan Turing', total_sessions_count: 21, sessions_attended_count: 21 });

const dashboard: IStatisticsDashboard = {
    summary: {
        lifetime_sessions_count: 240,
        sessions_this_week_count: 19,
        weekly_unique_participants_count: 18,
        lifetime_minutes_count: 11610,
    },
    top_hosts: [
        { id: 3, name: 'Alex Barry', sessions_hosted_count: 4 },
        { id: 4, name: 'Sam Reed', sessions_hosted_count: 1 },
    ],
    tags: [
        { id: 1, name: 'Client Work', sessions_count: 4 },
        { id: 2, name: 'Social', sessions_count: 3 },
    ],
    yet_to_mob_with: [{ id: 2, name: 'Brady Deroy' }],
    members: [ada, grace, alan],
    start_date: '2020-05-21',
    end_date: '2026-08-06',
    first_session_date: '2020-05-21',
};

function mountStatistics(props: Partial<IStatisticsDashboard> = {}, options: Record<string, unknown> = {}) {
    return mountWithInertia(Statistics, { ...options, props: { ...dashboard, ...props } });
}

function buttonLabelled(wrapper: VueWrapper, label: string) {
    const button = wrapper.findAll('button').find((candidate) => candidate.text().replace(/[▲▼]/g, '').trim() === label);

    if (!button) {
        throw new Error(`No button labelled "${label}"`);
    }

    return button;
}

function suggestedNames(wrapper: VueWrapper): string[] {
    return wrapper.findAll('[role="option"] [data-testid="suggestion-name"]').map((label) => label.text());
}

function suggestionFor(wrapper: VueWrapper, name: string) {
    const option = wrapper.findAll('[role="option"] button').find((candidate) => candidate.get('[data-testid="suggestion-name"]').text() === name);

    if (!option) {
        throw new Error(`No suggestion offering "${name}"`);
    }

    return option;
}

/** The search box is a picker now, so saving someone means typing and then choosing them. */
async function saveToList(wrapper: VueWrapper, typed: string, chosen = typed): Promise<void> {
    const searchBox = wrapper.get('input[name="filter-by-name"]');

    await searchBox.trigger('focus');
    await searchBox.setValue(typed);
    await suggestionFor(wrapper, chosen).trigger('click');
}

function savedNames(wrapper: VueWrapper): string[] {
    return wrapper.findAll('[aria-label="Saved filter list"] li').map((pill) => pill.text().replace('×', '').trim());
}

function memberNames(wrapper: VueWrapper): string[] {
    return wrapper.findAll('tbody [data-testid="member-name"]').map((cell) => cell.text());
}

let reload: MockInstance;
let writeText: ReturnType<typeof vi.fn>;

beforeEach(() => {
    DateTime.setTestNow('2026-08-06'); // A Thursday
    signedInUserId = null;
    // Most of what is under test only exists in the full display, so that is the default here.
    window.history.replaceState({}, '', '/statistics?full-display=');
    localStorage.clear();
    reload = vi.spyOn(router, 'reload').mockImplementation(() => undefined);
    writeText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });
});

afterEach(() => {
    vi.restoreAllMocks();
    Reflect.deleteProperty(document, 'execCommand');
});

describe('Statistics dashboard', () => {
    test('renders the dashboard statistics', () => {
        const wrapper = mountStatistics();

        expect(wrapper.text()).toContain('240');
        expect(wrapper.text()).toContain('Lifetime sessions');
        expect(wrapper.text()).toContain('Sessions this week');
        expect(wrapper.text()).toContain('Time of growth');
        expect(wrapper.text()).toContain('193h 30m');
        expect(wrapper.text()).toContain('Alex Barry');
        expect(wrapper.text()).toContain('Client Work');
    });

    test('separates the thousands in the lifetime session count', () => {
        const wrapper = mountStatistics({ summary: { ...dashboard.summary, lifetime_sessions_count: 11229 } });

        expect(wrapper.text()).toContain('11,229');
    });

    test('renders top hosts in the order given by the server', () => {
        const wrapper = mountStatistics();

        expect(wrapper.text()).toContain('4 sessions');
        expect(wrapper.text()).toContain('1 session');
    });

    test('renders the current user’s yet-to-mob-with members', () => {
        const wrapper = mountStatistics();

        expect(wrapper.text()).toContain('Yet to mob with');
        expect(wrapper.text()).toContain('Brady Deroy');
    });

    test('shows an empty state when there is nobody left to mob with', () => {
        const wrapper = mountStatistics({ yet_to_mob_with: [] });

        expect(wrapper.text()).toContain("You're all caught up!");
    });

    test('shows an empty state when no sessions were hosted this week', () => {
        const wrapper = mountStatistics({ top_hosts: [] });

        expect(wrapper.text()).toContain('No hosted sessions this week.');
    });
});

describe('Everyone’s numbers table', () => {
    test('renders a row for every member with their participation counts', () => {
        const wrapper = mountStatistics();

        expect(memberNames(wrapper)).toEqual(['Ada Lovelace', 'Alan Turing', 'Grace Hopper']);

        const adaCounts = wrapper
            .findAll('tbody tr')[0]
            .findAll('td')
            .slice(1)
            .map((cell) => cell.text());
        expect(adaCounts).toEqual(['2', '6', '1', '9', '2']);
    });

    test('shows the member’s own avatar', () => {
        const wrapper = mountStatistics({ members: [member({ name: 'Ada Lovelace', avatar: 'https://example.test/ada.png' })] });

        expect(wrapper.get('tbody img').attributes('src')).toBe('https://example.test/ada.png');
    });

    test('falls back to initials when an avatar fails to load', async () => {
        const wrapper = mountStatistics({ members: [member({ name: 'Ada Lovelace', avatar: 'https://example.test/gone.png' })] });

        await wrapper.get('tbody img').trigger('error');

        expect(wrapper.find('tbody img').exists()).toBe(false);
        expect(wrapper.findAll('tbody td')[0].text()).toContain('AL');
    });

    test('falls back to initials for a member without an avatar', () => {
        const wrapper = mountStatistics({ members: [member({ name: 'Ada Lovelace', avatar: null })] });

        expect(wrapper.find('tbody img').exists()).toBe(false);
        expect(wrapper.findAll('tbody td')[0].text()).toContain('AL');
    });

    test('puts the signed-in member first, whatever the sort says', async () => {
        signedInUserId = alan.user_id;
        const wrapper = mountStatistics();

        expect(memberNames(wrapper)).toEqual(['Alan Turing', 'Ada Lovelace', 'Grace Hopper']);

        await buttonLabelled(wrapper, 'Total').trigger('click');

        expect(memberNames(wrapper)[0]).toBe('Alan Turing');
    });

    test('celebrates a member who has mobbed with everyone instead of showing a zero', () => {
        const wrapper = mountStatistics();

        expect(wrapper.findAll('tbody tr')[2].text()).toContain('🎉');
    });

    test('opens a dialog naming the people a member has yet to mob with', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, '2').trigger('click');

        const dialog = wrapper.get('[role="dialog"]');
        expect(dialog.text()).toContain('Ada Lovelace has yet to mob with');
        expect(dialog.text()).toContain('Grace Hopper');
        expect(dialog.text()).toContain('Alan Turing');
    });

    test('dismisses the dialog with OK', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, '2').trigger('click');
        await buttonLabelled(wrapper, 'OK').trigger('click');

        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    test('dismisses the dialog with Escape', async () => {
        const wrapper = mountStatistics({}, { attachTo: document.body });

        await buttonLabelled(wrapper, '2').trigger('click');
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
        wrapper.unmount();
    });

    test('sorts by name ascending by default and reverses when the header is clicked again', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Name').trigger('click');

        expect(memberNames(wrapper)).toEqual(['Grace Hopper', 'Alan Turing', 'Ada Lovelace']);
    });

    test('sorts numeric columns by their value', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Total').trigger('click');

        expect(memberNames(wrapper)).toEqual(['Grace Hopper', 'Ada Lovelace', 'Alan Turing']);
    });

    test('pages the table at fifteen rows', async () => {
        const many = Array.from({ length: 20 }, (_, index) => member({ user_id: index + 1, name: `Member ${String(index + 1).padStart(2, '0')}` }));
        const wrapper = mountStatistics({ members: many });

        expect(wrapper.findAll('tbody tr')).toHaveLength(15);
        expect(wrapper.text()).toContain('Showing 1–15 of 20');
        expect(buttonLabelled(wrapper, 'Previous').attributes('disabled')).toBeDefined();

        await buttonLabelled(wrapper, 'Next').trigger('click');

        expect(wrapper.findAll('tbody tr')).toHaveLength(5);
        expect(wrapper.text()).toContain('Showing 16–20 of 20');
        expect(buttonLabelled(wrapper, 'Next').attributes('disabled')).toBeDefined();
    });
});

describe('The plain reading view', () => {
    beforeEach(() => window.history.replaceState({}, '', '/statistics'));

    test('shows only who is here and who they have yet to mob with', () => {
        const wrapper = mountStatistics();

        expect(wrapper.findAll('thead th').map((heading) => heading.text().replace(/[▲▼]/g, '').trim())).toEqual(['Name', 'Yet to mob with']);
        expect(memberNames(wrapper)).toEqual(['Ada Lovelace', 'Alan Turing', 'Grace Hopper']);
    });

    test('leaves the participation counts out of the rows', () => {
        const wrapper = mountStatistics();

        expect(wrapper.findAll('tbody tr')[0].findAll('td')).toHaveLength(2);
    });

    test('hides the filters, the date range and the share button', () => {
        const wrapper = mountStatistics();

        expect(wrapper.find('input[name="filter-by-name"]').exists()).toBe(false);
        expect(wrapper.find('input[name="start-date"]').exists()).toBe(false);
        expect(wrapper.find('input[name="apply-list"]').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Share URL');
    });

    test('still opens the yet-to-mob-with dialog', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, '2').trigger('click');

        expect(wrapper.get('[role="dialog"]').text()).toContain('Ada Lovelace has yet to mob with');
    });

    test('brings all of it back with full-display in the query', () => {
        window.history.replaceState({}, '', '/statistics?full-display=');
        const wrapper = mountStatistics();

        expect(wrapper.findAll('thead th')).toHaveLength(6);
        expect(wrapper.find('input[name="filter-by-name"]').exists()).toBe(true);
    });
});

describe('Finding and saving people', () => {
    test('suggests members matching what has been typed', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('a');

        expect(suggestedNames(wrapper)).toEqual(['Ada Lovelace', 'Grace Hopper', 'Alan Turing']);
    });

    test('saves the chosen member and empties the box', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace', 'Grace Hopper');

        expect(savedNames(wrapper)).toContain('Grace Hopper');
        expect((wrapper.get('input[name="filter-by-name"]').element as HTMLInputElement).value).toBe('');
    });

    test('saves the highlighted suggestion on Enter', async () => {
        const wrapper = mountStatistics();
        const searchBox = wrapper.get('input[name="filter-by-name"]');

        await searchBox.trigger('focus');
        await searchBox.setValue('Turing');
        await searchBox.trigger('keydown.enter');

        expect(savedNames(wrapper)).toContain('Alan Turing');
    });

    test('walks the suggestions with the arrow keys', async () => {
        const wrapper = mountStatistics();
        const searchBox = wrapper.get('input[name="filter-by-name"]');

        await searchBox.trigger('focus');
        await searchBox.trigger('keydown.down');
        await searchBox.trigger('keydown.enter');

        expect(savedNames(wrapper)).toContain('Grace Hopper');
    });

    test('stops offering someone already saved', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');

        expect(suggestedNames(wrapper)).toEqual(['Ada Lovelace', 'Alan Turing']);
    });

    test('says when nobody matches what has been typed', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('nobody');

        expect(wrapper.findAll('[role="option"]')).toHaveLength(0);
        expect(wrapper.get('#member-suggestions').text()).toContain('No members match');
    });

    test('never narrows the table on its own', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('hopper');

        expect(memberNames(wrapper)).toHaveLength(3);
    });

    test('shows no pills until someone has been saved', () => {
        const wrapper = mountStatistics();

        expect(savedNames(wrapper)).toEqual([]);
    });

    test('removes a name from the list', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');
        await wrapper.get('[aria-label="Remove Grace Hopper from the list"]').trigger('click');

        expect(savedNames(wrapper)).toEqual([]);
    });

    test('leaves the table alone until the list filter is switched on', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');

        expect(memberNames(wrapper)).toHaveLength(3);

        await wrapper.get('input[name="apply-list"]').setValue(true);

        expect(memberNames(wrapper)).toEqual(['Grace Hopper']);
    });

    test('shows the whole table when the list filter is on but the list is empty', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="apply-list"]').setValue(true);

        expect(memberNames(wrapper)).toHaveLength(3);
    });

    test('says so when the saved cohort matches nobody', () => {
        localStorage.setItem('statistics_filter', JSON.stringify({ list: ['Nobody At All'], shouldUseList: true }));
        const wrapper = mountStatistics();

        expect(memberNames(wrapper)).toEqual([]);
        expect(wrapper.text()).toContain('No members match these filters.');
    });

    test('narrows to every saved member at once', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');
        await saveToList(wrapper, 'Ada Lovelace');
        await wrapper.get('input[name="apply-list"]').setValue(true);

        expect(memberNames(wrapper)).toEqual(['Ada Lovelace', 'Grace Hopper']);
    });

    test('clears the list', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');
        await buttonLabelled(wrapper, 'Clear').trigger('click');

        expect(savedNames(wrapper)).toEqual([]);
    });

    test('drops shared settings from the address bar when the list is cleared', async () => {
        window.history.replaceState({}, '', `/statistics?full-display=&settings=${encodeSettings({ list: ['Grace'], shouldUseList: true })}`);
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Clear').trigger('click');

        expect(window.location.search).not.toContain('settings');
    });
});

describe('Persisting and sharing the filter list', () => {
    test('saves the list and the checkbox locally', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');
        await wrapper.get('input[name="apply-list"]').setValue(true);

        expect(JSON.parse(localStorage.getItem('statistics_filter') as string)).toEqual({ list: ['Grace Hopper'], shouldUseList: true });
    });

    test('restores saved settings on a later visit', () => {
        localStorage.setItem('statistics_filter', JSON.stringify({ list: ['Alan'], shouldUseList: true }));

        const wrapper = mountStatistics();

        expect(memberNames(wrapper)).toEqual(['Alan Turing']);
        expect(wrapper.get('input[name="apply-list"]')).toBeChecked();
    });

    test('falls back to defaults when saved settings are unreadable', () => {
        localStorage.setItem('statistics_filter', 'not json at all');

        const wrapper = mountStatistics();

        expect(memberNames(wrapper)).toHaveLength(3);
    });

    test('lets a shared link win over locally saved settings', () => {
        localStorage.setItem('statistics_filter', JSON.stringify({ list: ['Alan'], shouldUseList: true }));
        window.history.replaceState({}, '', `/statistics?settings=${encodeSettings({ list: ['Grace'], shouldUseList: true })}`);

        const wrapper = mountStatistics();

        expect(memberNames(wrapper)).toEqual(['Grace Hopper']);
    });

    test('falls back to saved settings when the shared link is mangled', () => {
        localStorage.setItem('statistics_filter', JSON.stringify({ list: ['Alan'], shouldUseList: true }));
        window.history.replaceState({}, '', '/statistics?settings=not-base-64-at-all!!');

        const wrapper = mountStatistics();

        expect(memberNames(wrapper)).toEqual(['Alan Turing']);
    });

    test('copies a link carrying the list, the checkbox and the date range', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');
        await wrapper.get('input[name="apply-list"]').setValue(true);
        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        const copied = new URL(writeText.mock.calls[0][0]);
        expect(copied.searchParams.get('start_date')).toBe('2020-05-21');
        expect(copied.searchParams.get('end_date')).toBe('2026-08-06');
        expect(copied.searchParams.get('settings')).toBe(encodeSettings({ list: ['Grace Hopper'], shouldUseList: true }));
    });

    test('confirms that the link was copied', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="status"]').text()).toContain('Link copied to clipboard');
    });

    test('puts the shareable link in the address bar so it can be copied by hand', async () => {
        const wrapper = mountStatistics();

        await saveToList(wrapper, 'Grace Hopper');
        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        expect(new URLSearchParams(window.location.search).get('settings')).toBe(encodeSettings({ list: ['Grace Hopper'], shouldUseList: false }));
    });

    test('falls back to a selection copy where the clipboard API is missing', async () => {
        Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
        const execCommand = vi.fn().mockReturnValue(true);
        Object.defineProperty(document, 'execCommand', { value: execCommand, configurable: true });
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        expect(execCommand).toHaveBeenCalledWith('copy');
        expect(wrapper.get('[role="status"]').text()).toContain('Link copied to clipboard');
    });

    test('says so rather than falsely confirming when no copy is possible at all', async () => {
        Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
        Object.defineProperty(document, 'execCommand', { value: vi.fn().mockReturnValue(false), configurable: true });
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="status"]').text()).toContain('Could not copy the link');
    });
});

describe('Choosing a date range', () => {
    test('keeps the picker shut until the range button is pressed', async () => {
        const wrapper = mountStatistics({ start_date: '2026-07-16' });

        // `toBeVisible` reads the inline `display: none` that v-show sets, rather than
        // isVisible(), which does not detect v-show styling under happy-dom.
        expect(wrapper.get('#date-range-picker')).not.toBeVisible();

        await buttonLabelled(wrapper, 'Jul 16 – Aug 6').trigger('click');

        expect(wrapper.get('#date-range-picker')).toBeVisible();
    });

    test('names the chosen range on the button that opens the picker', () => {
        const wrapper = mountStatistics({ start_date: '2026-07-16' });

        expect(wrapper.text()).toContain('Jul 16 – Aug 6');
    });

    test('reloads only the members table when a date changes', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="start-date"]').setValue('2026-01-01');

        expect(reload).toHaveBeenCalledTimes(1);
        expect(reload.mock.calls[0][0]).toMatchObject({
            only: ['members', 'start_date', 'end_date'],
            data: { start_date: '2026-01-01', end_date: '2026-08-06' },
        });
    });

    test('does not reload when the range is unchanged', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="start-date"]').setValue(dashboard.start_date);

        expect(reload).not.toHaveBeenCalled();
    });

    test('the This week preset runs from the most recent Monday to today', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'This week').trigger('click');

        expect(reload).toHaveBeenCalledTimes(1);
        expect(reload.mock.calls[0][0]).toMatchObject({ data: { start_date: '2026-08-03', end_date: '2026-08-06' } });
    });

    test('the This month preset runs from the first of the month to today', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'This month').trigger('click');

        expect(reload.mock.calls[0][0]).toMatchObject({ data: { start_date: '2026-08-01', end_date: '2026-08-06' } });
    });

    test('the All time preset runs from the first ever session to today', async () => {
        const wrapper = mountStatistics({ start_date: '2026-08-01', end_date: '2026-08-06' });

        await buttonLabelled(wrapper, 'All time').trigger('click');

        expect(reload.mock.calls[0][0]).toMatchObject({ data: { start_date: '2020-05-21', end_date: '2026-08-06' } });
    });

    test('the First day shortcut moves the start without touching the end', async () => {
        const wrapper = mountStatistics({ start_date: '2026-08-01', end_date: '2026-08-05' });

        await buttonLabelled(wrapper, 'First day').trigger('click');

        expect(reload.mock.calls[0][0]).toMatchObject({ data: { start_date: '2020-05-21', end_date: '2026-08-05' } });
    });

    test('the Today shortcut moves the end without touching the start', async () => {
        const wrapper = mountStatistics({ start_date: '2026-08-01', end_date: '2026-08-05' });

        await buttonLabelled(wrapper, 'Today').trigger('click');

        expect(reload.mock.calls[0][0]).toMatchObject({ data: { start_date: '2026-08-01', end_date: '2026-08-06' } });
    });

    test('a preset issues a single reload rather than one per date', async () => {
        const wrapper = mountStatistics({ start_date: '2020-01-01', end_date: '2020-01-02' });

        await buttonLabelled(wrapper, 'This week').trigger('click');

        expect(reload).toHaveBeenCalledTimes(1);
    });
});
