import { DateTime } from '@/classes/DateTime';
import { IUserStatistics } from '@/types';

/** Columns of the member statistics table that can be sorted on. */
export type SortableField =
    | 'name'
    | 'sessions_hosted_count'
    | 'sessions_attended_count'
    | 'sessions_watched_count'
    | 'total_sessions_count'
    | 'has_not_mobbed_with_count';

export type SortDirection = 'asc' | 'desc';

export interface DateRange {
    startDate: string;
    endDate: string;
}

/**
 * The slice of table state that is worth keeping between visits and worth putting in a
 * shareable link. The date range is not in here — it travels as ordinary `start_date` and
 * `end_date` query parameters so the server can resolve it before the page renders. The
 * live name box, the sort and the page are left out as momentary.
 */
export interface StatisticsSettings {
    list: string[];
    shouldUseList: boolean;
}

export interface MemberFilter {
    name: string;
    list: string[];
    shouldUseList: boolean;
}

function matchesName(member: IUserStatistics, needle: string): boolean {
    return member.name.toLowerCase().includes(needle.toLowerCase());
}

/**
 * The name box and the saved list compose: the list picks the cohort, the name box
 * searches within it. An empty list never narrows anything, even with the box ticked.
 */
export function filterMembers(members: IUserStatistics[], filter: MemberFilter): IUserStatistics[] {
    const inCohort =
        filter.shouldUseList && filter.list.length > 0
            ? members.filter((member) => filter.list.some((listedName) => matchesName(member, listedName)))
            : members;

    return inCohort.filter((member) => matchesName(member, filter.name));
}

export function sortMembers(members: IUserStatistics[], field: SortableField, direction: SortDirection): IUserStatistics[] {
    const multiplier = direction === 'asc' ? 1 : -1;

    return [...members].sort((left, right) => {
        const [a, b] = [left[field], right[field]];
        const comparison = typeof a === 'string' && typeof b === 'string' ? a.localeCompare(b) : Number(a) - Number(b);

        return comparison * multiplier;
    });
}

export function paginate<T>(rows: T[], page: number, perPage: number): T[] {
    return rows.slice((page - 1) * perPage, page * perPage);
}

export function totalPages(rowCount: number, perPage: number): number {
    return Math.max(1, Math.ceil(rowCount / perPage));
}

function today(): string {
    return DateTime.today().toDateString();
}

/** The Monday on or before the given date. Sunday closes a week here rather than opening one. */
function mondayOfWeek(date: string): string {
    const weekday = new DateTime(date).weekDayNumber();

    return new DateTime(date).addDays(-(weekday === 0 ? 6 : weekday - 1)).toDateString();
}

export function thisWeekRange(): DateRange {
    const endDate = today();

    return { startDate: mondayOfWeek(endDate), endDate };
}

/**
 * Growth Session weeks run Monday to Friday, so a month starts at its first Monday rather
 * than its first day. Early in a month whose first Monday is still ahead, that would give
 * an inverted range, so this week stands in.
 */
export function thisMonthRange(): DateRange {
    const endDate = today();
    const firstOfMonth = new DateTime(endDate).format('YYYY-MM-01');
    const weekday = new DateTime(firstOfMonth).weekDayNumber();
    const firstMonday = new DateTime(firstOfMonth).addDays(weekday === 1 ? 0 : (8 - weekday) % 7).toDateString();

    return firstMonday > endDate ? thisWeekRange() : { startDate: firstMonday, endDate };
}

export function allTimeRange(firstSessionDate: string): DateRange {
    return { startDate: new DateTime(firstSessionDate).toDateString(), endDate: today() };
}

/** base64url, so the result survives a query string untouched and names outside Latin-1 survive at all. */
function toBase64Url(value: string): string {
    const bytes = new TextEncoder().encode(value);

    return btoa(Array.from(bytes, (byte) => String.fromCharCode(byte)).join(''))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

function fromBase64Url(value: string): string {
    const base64 = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(base64.length + ((4 - (base64.length % 4)) % 4), '=');

    return new TextDecoder().decode(Uint8Array.from(atob(padded), (character) => character.charCodeAt(0)));
}

export function encodeSettings(settings: StatisticsSettings): string {
    return toBase64Url(JSON.stringify(settings));
}

/** Returns null for anything that is not a settings object, so callers can fall back. */
export function decodeSettings(encoded: string): StatisticsSettings | null {
    try {
        const decoded = JSON.parse(fromBase64Url(encoded));

        const isSettings =
            typeof decoded === 'object' &&
            decoded !== null &&
            Array.isArray(decoded.list) &&
            decoded.list.every((entry: unknown) => typeof entry === 'string') &&
            typeof decoded.shouldUseList === 'boolean';

        return isSettings ? decoded : null;
    } catch {
        return null;
    }
}
