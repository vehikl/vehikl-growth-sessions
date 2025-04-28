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
    tags?: number[];
}
