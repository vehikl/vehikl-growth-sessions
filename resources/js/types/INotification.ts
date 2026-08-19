export type INotificationType =
    | 'growth_session_date_changed'
    | 'growth_session_time_changed'
    | 'growth_session_location_changed'
    | 'growth_session_deleted'
    | 'growth_session_comment_added';

export type INotificationChangeField = 'date' | 'start_time' | 'location';

export interface INotificationChange {
    field: INotificationChangeField;
    label: string;
    value: string;
}

export interface INotificationData {
    type: INotificationType;
    title: string;
    growth_session_id?: number;
    change?: INotificationChange;
    commenter?: string;
    commenter_id?: number;
    commenter_avatar?: string | null;
    date?: string;
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
