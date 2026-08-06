import { DateTime } from '@/classes/DateTime';
import {
    allTimeRange,
    decodeSettings,
    encodeSettings,
    filterMembers,
    paginate,
    sortMembers,
    thisMonthRange,
    thisWeekRange,
    totalPages,
} from '@/lib/statistics';
import { IUserStatistics } from '@/types';
import { beforeEach, describe, expect, test } from 'vitest';

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

const ada = member({ user_id: 1, name: 'Ada Lovelace', total_sessions_count: 9 });
const grace = member({ user_id: 2, name: 'Grace Hopper', total_sessions_count: 3 });
const alan = member({ user_id: 3, name: 'Alan Turing', total_sessions_count: 21 });
const members = [ada, grace, alan];

describe('filterMembers', () => {
    test('returns everyone when nothing is filtering', () => {
        expect(filterMembers(members, { name: '', list: [], shouldUseList: false })).toEqual(members);
    });

    test('matches the name box anywhere in the name, ignoring case', () => {
        expect(filterMembers(members, { name: 'LOVE', list: [], shouldUseList: false })).toEqual([ada]);
    });

    test('ignores the list until it is switched on', () => {
        expect(filterMembers(members, { name: '', list: ['Grace'], shouldUseList: false })).toEqual(members);
    });

    test('keeps rows matching any name in the list once it is switched on', () => {
        expect(filterMembers(members, { name: '', list: ['grace', 'alan'], shouldUseList: true })).toEqual([grace, alan]);
    });

    test('falls back to everyone when the list is switched on but empty', () => {
        expect(filterMembers(members, { name: '', list: [], shouldUseList: true })).toEqual(members);
    });

    test('composes the name box with the list', () => {
        expect(filterMembers(members, { name: 'turing', list: ['grace', 'alan'], shouldUseList: true })).toEqual([alan]);
    });
});

describe('sortMembers', () => {
    test('sorts by name ascending', () => {
        expect(sortMembers(members, 'name', 'asc').map((row) => row.name)).toEqual(['Ada Lovelace', 'Alan Turing', 'Grace Hopper']);
    });

    test('sorts by name descending', () => {
        expect(sortMembers(members, 'name', 'desc').map((row) => row.name)).toEqual(['Grace Hopper', 'Alan Turing', 'Ada Lovelace']);
    });

    test('sorts numeric columns numerically', () => {
        expect(sortMembers(members, 'total_sessions_count', 'desc').map((row) => row.total_sessions_count)).toEqual([21, 9, 3]);
    });

    test('leaves the original array untouched', () => {
        sortMembers(members, 'name', 'desc');

        expect(members[0]).toBe(ada);
    });
});

describe('paginate', () => {
    const rows = Array.from({ length: 7 }, (_, index) => index);

    test('returns the requested slice', () => {
        expect(paginate(rows, 2, 3)).toEqual([3, 4, 5]);
    });

    test('returns a short final page', () => {
        expect(paginate(rows, 3, 3)).toEqual([6]);
    });

    test('counts pages, with one page when there is nothing to show', () => {
        expect(totalPages(7, 3)).toBe(3);
        expect(totalPages(0, 3)).toBe(1);
    });
});

describe('date range presets', () => {
    beforeEach(() => DateTime.setTestNow('2026-08-06')); // A Thursday

    test('this week runs from the most recent Monday to today', () => {
        expect(thisWeekRange()).toEqual({ startDate: '2026-08-03', endDate: '2026-08-06' });
    });

    test('this week on a Monday starts today rather than reaching back a week', () => {
        DateTime.setTestNow('2026-08-03');

        expect(thisWeekRange()).toEqual({ startDate: '2026-08-03', endDate: '2026-08-03' });
    });

    test('this week on a Sunday still starts on the Monday just gone', () => {
        DateTime.setTestNow('2026-08-09');

        expect(thisWeekRange()).toEqual({ startDate: '2026-08-03', endDate: '2026-08-09' });
    });

    test('this month runs from the first Monday of the month to today', () => {
        expect(thisMonthRange()).toEqual({ startDate: '2026-08-03', endDate: '2026-08-06' });
    });

    test('this month falls back to this week when the first Monday has not happened yet', () => {
        DateTime.setTestNow('2026-08-01'); // Saturday; the first Monday is the 3rd

        expect(thisMonthRange()).toEqual({ startDate: '2026-07-27', endDate: '2026-08-01' });
    });

    test('all time runs from the first ever session to today', () => {
        expect(allTimeRange('2020-05-21')).toEqual({ startDate: '2020-05-21', endDate: '2026-08-06' });
    });
});

describe('shareable settings', () => {
    test('round-trips the filter list and the checkbox', () => {
        const settings = { list: ['Ada'], shouldUseList: true };

        expect(decodeSettings(encodeSettings(settings))).toEqual(settings);
    });

    test('survives names outside the Latin-1 range', () => {
        const settings = { list: ['Zoë Küçük'], shouldUseList: false };

        expect(decodeSettings(encodeSettings(settings))).toEqual(settings);
    });

    test('returns null for a mangled value rather than throwing', () => {
        expect(decodeSettings('not-base64-at-all!!')).toBeNull();
    });

    test('returns null when the decoded value is not a settings object', () => {
        expect(decodeSettings(encodeSettings('a string' as never))).toBeNull();
    });
});
