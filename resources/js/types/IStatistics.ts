export interface IStatisticsSummary {
    lifetime_sessions_count: number;
    sessions_this_week_count: number;
    weekly_unique_participants_count: number;
    average_attendance_count: number;
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

/** Shape rendered by the Statistics page, built by ShowStatisticsController. */
export interface IStatisticsDashboard {
    summary: IStatisticsSummary;
    top_hosts: ITopHost[];
    tags: ITagUsage[];
    yet_to_mob_with: IStatisticsMember[];
}
