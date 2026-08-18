export interface INotificationData {
    type: 'growth_session_updated' | 'growth_session_deleted';
    title: string;
    growth_session_id?: number;
    changes?: string[];
    message?: string;
    url: string | null;
}

export interface INotification {
    id: string;
    data: INotificationData;
    read_at: string | null;
    created_at: string;
}

export interface INotificationIndexResponse {
    data: INotification[];
    unread_count: number;
}
