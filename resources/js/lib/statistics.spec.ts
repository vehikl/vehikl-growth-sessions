import { DateTime } from '@/classes/DateTime';
import {
    decodeSettings,
    encodeSettings,
    filterMembers,
    formatRangeLabel,
    lastMonthRange,
    lastWeekRange,
    paginate,
    sortMembers,
    suggestMembers,
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
        expect(filterMembers(members, { list: [], shouldUseList: false })).toEqual(members);
    });

    test('ignores the list until it is switched on', () => {
        expect(filterMembers(members, { list: ['Grace Hopper'], shouldUseList: false })).toEqual(members);
    });

    test('keeps rows matching any name in the list once it is switched on', () => {
        expect(filterMembers(members, { list: ['grace', 'alan'], shouldUseList: true })).toEqual([grace, alan]);
    });

    test('falls back to everyone when the list is switched on but empty', () => {
        expect(filterMembers(members, { list: [], shouldUseList: true })).toEqual(members);
    });
});

describe('suggestMembers', () => {
    test('offers everyone when nothing has been typed', () => {
        expect(suggestMembers(members, '', [], 8)).toEqual(members);
    });

    test('matches anywhere in the name, ignoring case and surrounding spaces', () => {
        expect(suggestMembers(members, '  LOVE ', [], 8)).toEqual([ada]);
    });

    test('leaves out anyone already saved to the list', () => {
        expect(suggestMembers(members, 'a', ['Ada Lovelace'], 8)).toEqual([grace, alan]);
    });

    test('stops at the limit rather than filling the screen', () => {
        expect(suggestMembers(members, '', [], 2)).toEqual([ada, grace]);
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

    test('last week runs Monday to Sunday of the week just gone', () => {
        expect(lastWeekRange()).toEqual({ startDate: '2026-07-27', endDate: '2026-08-02' });
    });

    test('last week on a Monday is the week before this brand new one', () => {
        DateTime.setTestNow('2026-08-03');

        expect(lastWeekRange()).toEqual({ startDate: '2026-07-27', endDate: '2026-08-02' });
    });

    test('last week on a Sunday is still the previous week, not the one closing today', () => {
        DateTime.setTestNow('2026-08-09');

        expect(lastWeekRange()).toEqual({ startDate: '2026-07-27', endDate: '2026-08-02' });
    });

    test('last month covers the whole of the previous calendar month', () => {
        expect(lastMonthRange()).toEqual({ startDate: '2026-07-01', endDate: '2026-07-31' });
    });

    test('last month crosses the year boundary in January', () => {
        DateTime.setTestNow('2026-01-14');

        expect(lastMonthRange()).toEqual({ startDate: '2025-12-01', endDate: '2025-12-31' });
    });
});

describe('formatRangeLabel', () => {
    beforeEach(() => DateTime.setTestNow('2026-08-06'));

    test('leaves the current year out', () => {
        expect(formatRangeLabel({ startDate: '2026-07-16', endDate: '2026-08-06' })).toBe('Jul 16 – Aug 6');
    });

    test('names the year on any side that is not the current one', () => {
        expect(formatRangeLabel({ startDate: '2020-05-21', endDate: '2026-08-06' })).toBe('May 21, 2020 – Aug 6');
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
