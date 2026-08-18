import NotificationBell from '@/components/NotificationBell.vue';
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
        type: 'growth_session_updated' as const,
        title: 'Weekly Pairing',
        growth_session_id: 5,
        changes: ['Location changed Zoom → The office'],
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
        message: '"Lightning Talks" was cancelled',
        url: null,
    },
};

describe('NotificationBell', () => {
    beforeEach(() => {
        vi.mocked(NotificationApi.index).mockResolvedValue(emptyResponse);
        vi.mocked(NotificationApi.markRead).mockResolvedValue(undefined);
    });

    it('fetches notifications on mount and shows the unread badge', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(NotificationBell, { props: { user } });
        await flushPromises();

        expect(wrapper.text()).toContain('1');
    });

    it('hides the badge when there are no unread notifications', async () => {
        const wrapper = mount(NotificationBell, { props: { user } });
        await flushPromises();

        expect(wrapper.find('button span').exists()).toBe(false);
    });

    it('opens the dropdown and lists recent notifications when the bell is clicked', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification, deletedNotification], unread_count: 2 });

        const wrapper = mount(NotificationBell, { props: { user } });
        await flushPromises();

        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toContain('Notifications');
        expect(wrapper.text()).toContain('Weekly Pairing');
        expect(wrapper.text()).toContain('was updated');
        expect(wrapper.text()).toContain('Location changed Zoom → The office');
        expect(wrapper.text()).toContain('Lightning Talks');
        expect(wrapper.text()).toContain('was cancelled');
    });

    it('links an updated notification to its session', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(NotificationBell, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.find('a[href="/growth_sessions/5"]').exists()).toBe(true);
    });

    it('renders a deleted notification as plain text with no link', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [deletedNotification], unread_count: 1 });

        const wrapper = mount(NotificationBell, { props: { user } });
        await flushPromises();
        await wrapper.find('button').trigger('click');

        expect(wrapper.find('a').exists()).toBe(false);
    });

    it('marks all notifications as read when the dropdown is opened', async () => {
        vi.mocked(NotificationApi.index).mockResolvedValue({ data: [updatedNotification], unread_count: 1 });

        const wrapper = mount(NotificationBell, { props: { user } });
        await flushPromises();

        await wrapper.find('button').trigger('click');
        await flushPromises();

        expect(NotificationApi.markRead).toHaveBeenCalledTimes(1);
        expect(wrapper.find('button span').exists()).toBe(false);
    });
});
