/** Mirrors App\Enums\NotificationType. */
export type NotificationType = 'gs_comment' | 'gs_time' | 'gs_location' | 'gs_time_location' | 'gs_deleted';

/**
 * The growth session a notification is about. Null when there is nothing left to describe, and
 * `id` is null for a deleted session — it is served from the snapshot taken before the row went,
 * so there is nothing to link to.
 */
export interface INotificationGrowthSession {
    id: number | null;
    title: string | null;
    location: string | null;
    date: string | null;
    start_time: string | null;
    end_time: string | null;
}

export interface INotificationInitiator {
    id: number;
    name: string;
    /** Nullable: `users.avatar` is only populated for members who have one. */
    avatar: string | null;
}

export interface INotification {
    id: number;
    type: NotificationType;
    read: boolean;
    growth_session: INotificationGrowthSession | null;
    initiator: INotificationInitiator | null;
    created_at: string;
}
