import type { ITag, ITagUsage } from './ITag';
import type { IMemberSummary } from './IUser';

/** One row of the Dashboard's hosted sessions list, built server-side by HostedGrowthSession. */
export interface IHostedSession {
    id: number;
    title: string;
    /** The raw date the server sorted on. `date_label` is what the list renders. */
    date: string;
    date_label: string;
    /** Drives the status dot, so the row never has to reason about "today" in the browser. */
    is_upcoming: boolean;
    /** Everyone in the room except the host, so a session nobody joined reads as zero. */
    attendee_count: number;
    tags: ITag[];
}

/** Totals across the user's whole history, not just the page on screen. */
export interface ISessionSummary {
    sessions_hosted_count: number;
    /** Sessions somebody else ran that this user joined. */
    sessions_attended_count: number;
    upcoming_count: number;
    total_attendees_count: number;
}

/** The orders the hosted sessions list offers. The server owns the direction of each. */
export type IHostedSessionSort = 'date' | 'name' | 'attendees';

/** The slice of Laravel's length-aware paginator payload the Dashboard renders. */
export interface IPaginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

/** A Vehikl member the signed-in user has never been in a Growth Session with. */
export type IMemberYetToMobWith = IMemberSummary;

/** Somebody the signed-in user has mobbed with, and how often. */
export interface IMobSquadMember extends IMemberSummary {
    /** Sessions the two of them were both in the room for, hosted or joined alike. */
    sessions_together_count: number;
}

/** Props rendered by the Dashboard page, built by ShowDashboardController. */
export interface IDashboard {
    summary: ISessionSummary;
    hosted_sessions: IPaginated<IHostedSession>;
    /** Which order the list came back in, so the controls can show the active one. */
    sort: IHostedSessionSort;
    /** At most five, busiest first, counted over the sessions this user hosts. */
    top_tags: ITagUsage[];
    /** At most five, most-mobbed-with first. Nobody sits at zero: they simply aren't listed. */
    mob_squad: IMobSquadMember[];
    yet_to_mob_with: IMemberYetToMobWith[];
}
