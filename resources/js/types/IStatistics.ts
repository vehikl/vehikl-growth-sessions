export interface IStatisticsSummary {
    lifetime_sessions_count: number;
    sessions_this_week_count: number;
    weekly_unique_participants_count: number;
    lifetime_minutes_count: number;
}

export interface ITopHost {
    id: number;
    name: string;
    sessions_hosted_count: number;
}

export interface ITagUsage {
    id: number;
    name: string;
    sessions_count: number;
}

export interface IStatisticsMember {
    id: number;
    name: string;
}

/**
 * One member's participation over the selected date range. Built by the Statistics action
 * and trimmed by ShowStatisticsController, which drops the `has_mobbed_with` half of the
 * matrix because nothing renders it.
 */
export interface IUserStatistics {
    user_id: number;
    name: string;
    /** Absent for members whose statistics were cached before avatars were carried here. */
    avatar?: string | null;
    sessions_hosted_count: number;
    sessions_attended_count: number;
    sessions_watched_count: number;
    total_sessions_count: number;
    has_mobbed_with_count: number;
    has_not_mobbed_with_count: number;
    has_not_mobbed_with: IStatisticsMember[];
}

/** Shape rendered by the Statistics page, built by ShowStatisticsController. */
export interface IStatisticsDashboard {
    summary: IStatisticsSummary;
    top_hosts: ITopHost[];
    tags: ITagUsage[];
    members: IUserStatistics[];
    start_date: string;
    end_date: string;
    first_session_date: string;
}
