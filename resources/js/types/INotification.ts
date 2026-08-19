export type INotificationChangeField = 'date' | 'start_time' | 'location';

export interface INotificationChange {
    field: INotificationChangeField;
    label: string;
    value: string;
}

interface IBaseNotificationData {
    title: string;
    url: string | null;
}

export interface IGrowthSessionChangeNotificationData extends IBaseNotificationData {
    type: 'growth_session_date_changed' | 'growth_session_time_changed' | 'growth_session_location_changed';
    growth_session_id?: number;
    change?: INotificationChange;
}

export interface IGrowthSessionDeletedNotificationData extends IBaseNotificationData {
    type: 'growth_session_deleted';
    date?: string;
}

export interface IGrowthSessionCommentAddedNotificationData extends IBaseNotificationData {
    type: 'growth_session_comment_added';
    growth_session_id?: number;
    commenter?: string;
    commenter_id?: number;
    commenter_avatar?: string | null;
}

/** Discriminated on `type` — narrow with `notification.data.type === '...'` to reach a variant's own fields. */
export type INotificationData = IGrowthSessionChangeNotificationData | IGrowthSessionDeletedNotificationData | IGrowthSessionCommentAddedNotificationData;

export type INotificationType = INotificationData['type'];

export interface INotification {
    id: string;
    data: INotificationData;
    read_at: string | null;
    created_at: string;
}

export interface INotificationIndexResponse {
    data: INotification[];
    unread_count: number;
    dropdown_limit: number;
}
