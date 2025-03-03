export interface IUserStatistics {
    name: string,
    user_id: number,

    has_mobbed_with: { name: string, id: number }[],
    has_mobbed_with_count: number,

    has_not_mobbed_with: { name: string, id: number }[],
    has_not_mobbed_with_count: number,

    total_sessions_count: number,
    sessions_hosted_count: number,
    sessions_attended_count: number,
    sessions_watched_count: number,
}

export interface IStatistics {
    start_date: string,
    end_date: string,
    users: IUserStatistics[]
}
