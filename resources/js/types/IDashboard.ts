import type { ITag } from './ITag';

/** One row of the Dashboard's hosted sessions list, built server-side by HostedGrowthSession. */
export interface IHostedSession {
    id: number;
    title: string;
    /** The raw date the server sorted on. `date_label` is what the list renders. */
    date: string;
    date_label: string;
    /** Drives the status dot, so the row never has to reason about "today" in the browser. */
    is_upcoming: boolean;
    time_label: string;
    /** Everyone in the room except the host, so a session nobody joined reads as zero. */
    attendee_count: number;
    tags: ITag[];
}

/** Totals across the user's whole hosting history, not just the page on screen. */
export interface IHostingSummary {
    sessions_hosted_count: number;
    upcoming_count: number;
    total_attendees_count: number;
}

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

/** Props rendered by the Dashboard page, built by ShowDashboardController. */
export interface IDashboard {
    summary: IHostingSummary;
    hosted_sessions: IPaginated<IHostedSession>;
}
