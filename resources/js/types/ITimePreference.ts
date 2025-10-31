export interface ITimePreference {
    id?: number;
    growth_session_proposal_id?: number;
    weekday: 'Monday' | 'Tuesday' | 'Wednesday' | 'Thursday' | 'Friday';
    start_time: string;
    end_time: string;
    created_at?: string;
    updated_at?: string;
}
