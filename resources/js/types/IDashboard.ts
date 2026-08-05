import type { ITag } from './ITag';

/** One row of the Dashboard's hosted sessions table, built server-side by HostedGrowthSession. */
export interface IHostedSession {
    id: number;
    title: string;
    /** The raw date the server sorted on. `date_label` is what the table renders. */
    date: string;
    date_label: string;
    time_label: string;
    /** Everyone in the room except the host, so a session nobody joined reads as zero. */
    attendee_count: number;
    tags: ITag[];
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
    hosted_sessions: IPaginated<IHostedSession>;
}
