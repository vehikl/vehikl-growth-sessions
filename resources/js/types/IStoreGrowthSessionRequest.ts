export interface IStoreGrowthSessionRequest {
    location: string;
    topic: string;
    title: string;
    date: string;
    start_time: string;
    end_time?: string;
    attendee_limit?: number | null;
    discord_channel_id?: string | null;
    anydesk_id?: number | null;
    is_public?: boolean;
    allow_watchers?: boolean;
    has_invite_link?: boolean;
    tags?: number[];
    /** The series to file this session under. Blank or absent files it under none. */
    series_name?: string | null;
}
