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

function matchesName(member: IUserStatistics, needle: string): boolean {
    return member.name.toLowerCase().includes(needle.toLowerCase());
}

/**
 * The saved list picks the cohort, and only the checkbox applies it. The search box no
 * longer narrows the table at all — it exists to pick people for the list, so typing in
 * it never hides the numbers someone is reading.
 */
export function filterMembers(members: IUserStatistics[], filter: StatisticsSettings): IUserStatistics[] {
    if (!filter.shouldUseList || filter.list.length === 0) {
        return members;
    }

    return members.filter((member) => filter.list.some((listedName) => matchesName(member, listedName)));
}

/**
 * Candidates for the search box, with anyone already saved left out — re-picking a name
 * is a no-op, so offering it is noise. An empty box offers everyone rather than nothing,
 * which makes the box usable by browsing as well as by typing.
 */
export function suggestMembers(members: IUserStatistics[], needle: string, saved: string[], limit: number): IUserStatistics[] {
    return members.filter((member) => !saved.includes(member.name) && matchesName(member, needle.trim())).slice(0, limit);
}

export function sortMembers(members: IUserStatistics[], field: SortableField, direction: SortDirection): IUserStatistics[] {
    const multiplier = direction === 'asc' ? 1 : -1;

    return [...members].sort((left, right) => {
        const [a, b] = [left[field], right[field]];
        const comparison = typeof a === 'string' && typeof b === 'string' ? a.localeCompare(b) : Number(a) - Number(b);

        return comparison * multiplier;
    });
}

/**
 * Lifts the signed-in member to the top, whatever the sort says. Their own row is the one
 * they came to read, and hunting for it through several pages of an alphabetical list is
 * the whole friction this removes. Everyone else keeps the order they arrived in.
 */
export function pinMember(members: IUserStatistics[], userId: number | null): IUserStatistics[] {
    const mine = userId === null ? -1 : members.findIndex((member) => member.user_id === userId);

    return mine <= 0 ? members : [members[mine], ...members.slice(0, mine), ...members.slice(mine + 1)];
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

/** The Monday just gone through to today, so the week fills in as it goes. */
export function thisWeekRange(): DateRange {
    const endDate = today();

    return { startDate: mondayOfWeek(endDate), endDate };
}

/** The first of this month through to today. */
export function thisMonthRange(): DateRange {
    const endDate = today();

    return { startDate: new DateTime(endDate).format('YYYY-MM-01'), endDate };
}

/** Everything on record: the first ever session through to today. */
export function allTimeRange(firstSessionDate: string): DateRange {
    return { startDate: new DateTime(firstSessionDate).toDateString(), endDate: today() };
}

/**
 * A span of minutes as hours and minutes — 11610 reads "193h 30m". A whole number of hours
 * drops the minutes rather than trailing a pointless "0m".
 */
export function formatGrowthTime(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const leftover = minutes % 60;

    return leftover === 0 ? `${hours.toLocaleString()}h` : `${hours.toLocaleString()}h ${leftover}m`;
}

/**
 * The range as it reads on the button that opens the date picker. Years are shown only
 * where they are not the current one, so an ordinary range stays short while a range
 * reaching back to the first ever session still says which year that was.
 */
export function formatRangeLabel({ startDate, endDate }: DateRange): string {
    const thisYear = new DateTime(today()).format('YYYY');
    const label = (date: string) => new DateTime(date).format(new DateTime(date).format('YYYY') === thisYear ? 'MMM D' : 'MMM D, YYYY');

    return `${label(startDate)} – ${label(endDate)}`;
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
