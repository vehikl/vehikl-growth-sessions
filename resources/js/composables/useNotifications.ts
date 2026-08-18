import { NotificationApi } from '@/services/NotificationApi';
import { INotification } from '@/types';
import { useEchoNotification } from '@laravel/echo-vue';
import { ref } from 'vue';

const DROPDOWN_LIMIT = 15;

export function useNotifications(userId: number) {
    const notifications = ref<INotification[]>([]);
    const unreadCount = ref(0);

    async function load() {
        const response = await NotificationApi.index();
        notifications.value = response.data;
        unreadCount.value = response.unread_count;
    }

    async function markAllRead() {
        if (unreadCount.value === 0) return;

        const now = new Date().toISOString();
        notifications.value = notifications.value.map((notification) => ({
            ...notification,
            read_at: notification.read_at ?? now,
        }));
        unreadCount.value = 0;

        await NotificationApi.markRead();
    }

    function handleIncoming(payload: INotification['data'] & { id: string }) {
        notifications.value = [
            {
                id: payload.id,
                data: payload,
                read_at: null,
                created_at: new Date().toISOString(),
            },
            ...notifications.value,
        ].slice(0, DROPDOWN_LIMIT);
        unreadCount.value += 1;
    }

    load();
    useEchoNotification<INotification['data'] & { id: string }>(`App.Models.User.${userId}`, handleIncoming);

    return { notifications, unreadCount, markAllRead };
}
