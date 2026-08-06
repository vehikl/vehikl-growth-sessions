import { DateTime } from '@/classes/DateTime';
import { encodeSettings } from '@/lib/statistics';
import Statistics from '@/pages/Statistics.vue';
import { mountWithInertia } from '@/test-utils/inertia-test-helper';
import { IStatisticsDashboard, IUserStatistics } from '@/types';
import { router } from '@inertiajs/vue3';
import { flushPromises, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, type MockInstance, test, vi } from 'vitest';

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
        average_attendance_count: 3.4,
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

function memberNames(wrapper: VueWrapper): string[] {
    return wrapper.findAll('tbody tr').map((row) => row.findAll('td')[0].text());
}

let reload: MockInstance;
let writeText: ReturnType<typeof vi.fn>;

beforeEach(() => {
    DateTime.setTestNow('2026-08-06'); // A Thursday
    window.history.replaceState({}, '', '/statistics');
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
        expect(wrapper.text()).toContain('Alex Barry');
        expect(wrapper.text()).toContain('Client Work');
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

        const adaRow = wrapper
            .findAll('tbody tr')[0]
            .findAll('td')
            .map((cell) => cell.text());
        expect(adaRow).toEqual(['Ada Lovelace', '2', '6', '1', '9', '2']);
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

    test('pages the table at twenty-five rows', async () => {
        const many = Array.from({ length: 30 }, (_, index) => member({ user_id: index + 1, name: `Member ${String(index + 1).padStart(2, '0')}` }));
        const wrapper = mountStatistics({ members: many });

        expect(wrapper.findAll('tbody tr')).toHaveLength(25);
        expect(wrapper.text()).toContain('Showing 1–25 of 30');
        expect(buttonLabelled(wrapper, 'Previous').attributes('disabled')).toBeDefined();

        await buttonLabelled(wrapper, 'Next').trigger('click');

        expect(wrapper.findAll('tbody tr')).toHaveLength(5);
        expect(wrapper.text()).toContain('Showing 26–30 of 30');
        expect(buttonLabelled(wrapper, 'Next').attributes('disabled')).toBeDefined();
    });
});

describe('Filtering the members table', () => {
    test('filters live on the name box, matching anywhere and ignoring case', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('hopper');

        expect(memberNames(wrapper)).toEqual(['Grace Hopper']);
    });

    test('says so when nothing matches', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('nobody');

        expect(memberNames(wrapper)).toEqual([]);
        expect(wrapper.text()).toContain('No members match these filters.');
    });

    test('adds the typed name to the list and clears the box', async () => {
        const wrapper = mountStatistics();
        const nameBox = wrapper.get('input[name="filter-by-name"]');

        await nameBox.setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');

        expect(wrapper.get('[aria-label="Saved filter list"]').text()).toContain('Grace');
        expect((nameBox.element as HTMLInputElement).value).toBe('');
    });

    test('adds the typed name to the list on Enter', async () => {
        const wrapper = mountStatistics();

        const nameBox = wrapper.get('input[name="filter-by-name"]');
        await nameBox.setValue('Grace');
        await nameBox.trigger('keydown.enter');

        expect(wrapper.get('[aria-label="Saved filter list"]').text()).toContain('Grace');
    });

    test('ignores a name already in the list', async () => {
        const wrapper = mountStatistics();
        const nameBox = wrapper.get('input[name="filter-by-name"]');

        await nameBox.setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await nameBox.setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');

        expect(wrapper.findAll('[aria-label="Saved filter list"] li')).toHaveLength(1);
    });

    test('says the list is empty when it is', () => {
        const wrapper = mountStatistics();

        expect(wrapper.get('[aria-label="Saved filter list"]').text()).toContain('The list is empty');
    });

    test('removes a name from the list', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await wrapper.get('[aria-label="Remove Grace from the list"]').trigger('click');

        expect(wrapper.get('[aria-label="Saved filter list"]').text()).toContain('The list is empty');
    });

    test('leaves the table alone until the list filter is switched on', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');

        expect(memberNames(wrapper)).toHaveLength(3);

        await wrapper.get('input[name="apply-list"]').setValue(true);

        expect(memberNames(wrapper)).toEqual(['Grace Hopper']);
    });

    test('shows the whole table when the list filter is on but the list is empty', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="apply-list"]').setValue(true);

        expect(memberNames(wrapper)).toHaveLength(3);
    });

    test('composes the list filter with the name box', async () => {
        const wrapper = mountStatistics();
        const nameBox = wrapper.get('input[name="filter-by-name"]');

        await nameBox.setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await nameBox.setValue('Ada');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await wrapper.get('input[name="apply-list"]').setValue(true);
        await nameBox.setValue('lovelace');

        expect(memberNames(wrapper)).toEqual(['Ada Lovelace']);
    });

    test('clears the list', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await buttonLabelled(wrapper, 'Clear').trigger('click');

        expect(wrapper.get('[aria-label="Saved filter list"]').text()).toContain('The list is empty');
    });

    test('drops shared settings from the address bar when the list is cleared', async () => {
        window.history.replaceState({}, '', `/statistics?settings=${encodeSettings({ list: ['Grace'], shouldUseList: true })}`);
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Clear').trigger('click');

        expect(window.location.search).not.toContain('settings');
    });
});

describe('Persisting and sharing the filter list', () => {
    test('saves the list and the checkbox locally', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await wrapper.get('input[name="apply-list"]').setValue(true);

        expect(JSON.parse(localStorage.getItem('statistics_filter') as string)).toEqual({ list: ['Grace'], shouldUseList: true });
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

        await wrapper.get('input[name="filter-by-name"]').setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await wrapper.get('input[name="apply-list"]').setValue(true);
        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        const copied = new URL(writeText.mock.calls[0][0]);
        expect(copied.searchParams.get('start_date')).toBe('2020-05-21');
        expect(copied.searchParams.get('end_date')).toBe('2026-08-06');
        expect(copied.searchParams.get('settings')).toBe(encodeSettings({ list: ['Grace'], shouldUseList: true }));
    });

    test('confirms that the link was copied', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="status"]').text()).toContain('Link copied to clipboard');
    });

    test('puts the shareable link in the address bar so it can be copied by hand', async () => {
        const wrapper = mountStatistics();

        await wrapper.get('input[name="filter-by-name"]').setValue('Grace');
        await buttonLabelled(wrapper, 'Add to list').trigger('click');
        await buttonLabelled(wrapper, 'Share URL').trigger('click');
        await flushPromises();

        expect(new URLSearchParams(window.location.search).get('settings')).toBe(encodeSettings({ list: ['Grace'], shouldUseList: false }));
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

    test('the Last week preset runs Monday to Sunday of the week just gone', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Last week').trigger('click');

        expect(reload).toHaveBeenCalledTimes(1);
        expect(reload.mock.calls[0][0]).toMatchObject({ data: { start_date: '2026-07-27', end_date: '2026-08-02' } });
    });

    test('the Last month preset covers the whole of the previous calendar month', async () => {
        const wrapper = mountStatistics();

        await buttonLabelled(wrapper, 'Last month').trigger('click');

        expect(reload.mock.calls[0][0]).toMatchObject({ data: { start_date: '2026-07-01', end_date: '2026-07-31' } });
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

        await buttonLabelled(wrapper, 'Last week').trigger('click');

        expect(reload).toHaveBeenCalledTimes(1);
    });
});
