import type { ITagUsage } from './ITag';

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

/** Shape rendered by the Statistics page, built by ShowStatisticsController. */
export interface IStatisticsDashboard {
    summary: IStatisticsSummary;
    top_hosts: ITopHost[];
    tags: ITagUsage[];
}
