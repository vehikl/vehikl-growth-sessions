import Notifications from '@/components/Notifications.vue';
import { NotificationApi } from '@/services/NotificationApi';
import { INotificationIndexResponse, IUser } from '@/types';
import { mount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@laravel/echo-vue', () => ({
    default: vi.fn(),
    useEchoNotification: vi.fn(),
}));

vi.mock('@/services/NotificationApi', () => ({
    NotificationApi: {
        index: vi.fn(),
        markRead: vi.fn(),
    },
}));

const user: IUser = {
    id: 42,
    github_nickname: 'jackjack',
    name: 'Jack Bauer',
    avatar: 'jack.jpg',
    is_vehikl_member: true,
};

const emptyResponse: INotificationIndexResponse = { data: [], unread_count: 0 };

const updatedNotification = {
    id: 'a1',
    read_at: null,
    created_at: '2020-01-01T12:00:00.000Z',
    data: {
        type: 'growth_session_location_changed' as const,
        title: 'Weekly Pairing',
        growth_session_id: 5,
        change: { field: 'location' as const, label: 'Location', value: 'The office' },
        url: '/growth_sessions/5',
    },
};

const deletedNotification = {
    id: 'b2',
    read_at: null,
    created_at: '2020-01-02T12:00:00.000Z',
    data: {
        type: 'growth_session_deleted' as const,
        title: 'Lightning Talks',
        date: '2020-08-20',
        url: null,
    },
};

const commentNotification = {
    id: 'c3',
    read_at: null,
    created_at: '2020-01-03T12:00:00.000Z',
    data: {
        type: 'growth_session_comment_added' as const,
        title: 'Weekly Pairing',
        growth_session_id: 5,
        commenter: 'Jane Doe',
        commenter_id: 7,
        commenter_avatar: 'https://example.com/jane.jpg',
        url: '/growth_sessions/5',
    },
};

describe('Notifications', () => {
    beforeEach(() => {
        vi.mocked(NotificationApi.index).mockReset().mockResolvedValue(emptyResponse);
        vi.mocked(NotificationApi.markRead).mockReset().mockResolvedValue(undefined);
    });

    it('fetches notifications on mount and shows the unread badge', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();

        expect(wrapper.text()).toContain('1');
    });

    it('hides the badge when there are no unread notifications', async () => {
        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();

        expect(wrapper.find('button span').exists()).toBe(false);
    });

    it('opens the dropdown and lists recent notifications when the bell is clicked', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification, deletedNotification], unread_count: 2 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();

        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toContain('Notifications');
        expect(wrapper.text()).toContain('Weekly Pairing');
        expect(wrapper.text()).toContain('has been updated.');
        expect(wrapper.text()).toContain('Lightning Talks');
        expect(wrapper.text()).toContain('has been cancelled for Aug 20');
    });

    it('shows what the changed field is now set to', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toContain('Location is now The office.');
    });

    it('renders the change detail for a time-changed notification', async () => {
        const timeChangedNotification = {
            id: 'd4',
            read_at: null,
            created_at: '2020-01-04T12:00:00.000Z',
            data: {
                type: 'growth_session_time_changed' as const,
                title: 'Weekly Pairing',
                growth_session_id: 5,
                change: { field: 'start_time' as const, label: 'Start time', value: '11:00 AM' },
                url: '/growth_sessions/5',
            },
        };
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [timeChangedNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toContain('has been updated.');
        expect(wrapper.text()).toContain('Start time is now 11:00 AM.');
    });

    it('links an updated notification to its session', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.find('a[href="/growth_sessions/5"]').exists()).toBe(true);
    });

    it('renders a deleted notification as plain text with no link', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [deletedNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.find('a').exists()).toBe(false);
    });

    it('renders a comment notification with the commenter', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [commentNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toContain('Jane Doe');
        expect(wrapper.text()).toContain('commented on Weekly Pairing');
        expect(wrapper.find('a[href="/growth_sessions/5"]').exists()).toBe(true);

        const avatarImg = wrapper.find('img');
        expect(avatarImg.exists()).toBe(true);
        expect(avatarImg.attributes('src')).toBe('https://example.com/jane.jpg');
    });

    it('falls back to initials when a comment notification has no avatar', async () => {
        const commentWithoutAvatar = {
            ...commentNotification,
            id: 'c4',
            data: { ...commentNotification.data, commenter_avatar: undefined },
        };
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [commentWithoutAvatar], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.text()).toContain('JD');
    });

    it('does not render an avatar for non-comment notifications', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.find('img').exists()).toBe(false);
    });

    it('marks all notifications as read when the dropdown is opened', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(Notifications, { props: { user } });
        await flushPromises();

        await wrapper.find('button').trigger('click');
        await flushPromises();

        expect(NotificationApi.markRead).toHaveBeenCalledTimes(1);
        expect(wrapper.find('button span').exists()).toBe(false);
    });
});
