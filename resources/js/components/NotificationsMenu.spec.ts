import NotificationsMenu from '@/components/NotificationsMenu.vue';
import { notificationSentence } from '@/lib/notificationSentence';
import { NotificationApi } from '@/services/NotificationApi';
import type { INotification } from '@/types';
import { mount, type VueWrapper } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { vi } from 'vitest';

// Spied rather than stubbed: the sentences below are the real ones, we just want to count the work.
vi.mock('@/lib/notificationSentence', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/lib/notificationSentence')>();

    return { ...actual, notificationSentence: vi.fn(actual.notificationSentence) };
});

function aNotification(overrides: Partial<INotification> = {}): INotification {
    return {
        id: 1,
        event_types: ['gs_comment'],
        read: false,
        growth_session: {
            id: 42,
            title: 'Pairing on Vue',
            location: 'AnyDesk 12',
            date: '2026-08-20',
            start_time: '03:30 pm',
            end_time: '05:00 pm',
        },
        initiator: { id: 7, name: 'Ada', avatar: 'ada.jpg' },
        created_at: '2026-08-17T18:32:00.000000Z',
        ...overrides,
    };
}

async function menuShowing(notifications: INotification[]): Promise<VueWrapper> {
    NotificationApi.index = vi.fn().mockResolvedValue(notifications);
    const wrapper = mount(NotificationsMenu);
    await flushPromises();
    await wrapper.find('[data-testid="notifications-trigger"]').trigger('click');
    await flushPromises();
    return wrapper;
}

describe('NotificationsMenu', () => {
    // Braces matter: a function returned from beforeEach is registered as a cleanup callback and called.
    beforeEach(() => {
        vi.mocked(notificationSentence).mockClear();
    });

    afterEach(() => vi.restoreAllMocks());

    it('asks the endpoint for the notifications as soon as it mounts', async () => {
        NotificationApi.index = vi.fn().mockResolvedValue([]);

        mount(NotificationsMenu);
        await flushPromises();

        expect(NotificationApi.index).toHaveBeenCalled();
    });

    it('lists one row per notification', async () => {
        const wrapper = await menuShowing([aNotification({ id: 1 }), aNotification({ id: 2 }), aNotification({ id: 3 })]);

        expect(wrapper.findAll('[data-testid="notification"]')).toHaveLength(3);
    });

    it('reads the notification as a sentence rather than its raw events', async () => {
        const wrapper = await menuShowing([aNotification({ event_types: ['gs_comment'] })]);

        expect(wrapper.find('[data-testid="notification-sentence"]').text()).toBe('Ada commented on Pairing on Vue');
        expect(wrapper.text()).not.toContain('gs_comment');
    });

    it('shows the time it was created', async () => {
        const wrapper = await menuShowing([aNotification({ created_at: '2026-08-17T18:32:00.000000Z' })]);

        // Tests run in America/Toronto, so 18:32 UTC lands in the afternoon.
        expect(wrapper.find('[data-testid="notification-created-at"]').text()).toBe('Aug 17, 2:32 pm');
    });

    // The sentences themselves are covered in notificationSentence.spec.ts; this pins the wiring.
    it('reads every notification through the same translation', async () => {
        const wrapper = await menuShowing([
            aNotification({ id: 1, event_types: ['gs_time'] }),
            aNotification({ id: 2, event_types: ['gs_date', 'gs_location'] }),
            aNotification({ id: 3, event_types: ['gs_deleted'] }),
        ]);

        const sentences = wrapper.findAll('[data-testid="notification-sentence"]').map((row) => row.text());

        expect(sentences).toEqual([
            'Ada updated the time of Pairing on Vue, now 03:30 pm to 05:00 pm',
            'Ada updated the date and location of Pairing on Vue, now Aug 20, at AnyDesk 12',
            'Ada deleted Pairing on Vue',
        ]);
    });

    it('shows the initiator behind the notification', async () => {
        const wrapper = await menuShowing([aNotification({ initiator: { id: 7, name: 'Ada Lovelace', avatar: 'ada.jpg' } })]);

        expect(wrapper.find('[data-testid="notification-avatar"] img').attributes()).toMatchObject({ src: 'ada.jpg', alt: 'Ada Lovelace' });
    });

    it('falls back to initials when the initiator has no avatar', async () => {
        const wrapper = await menuShowing([aNotification({ initiator: { id: 7, name: 'Ada Lovelace', avatar: null } })]);

        const avatar = wrapper.find('[data-testid="notification-avatar"]');

        expect(avatar.find('img').exists()).toBe(false);
        expect(avatar.text()).toBe('AL');
    });

    // The sentence says "Someone", so the avatar beside it must not claim to be anybody else.
    it('shows the same stand-in as the sentence when there is no initiator', async () => {
        const wrapper = await menuShowing([aNotification({ initiator: null })]);

        expect(wrapper.find('[data-testid="notification-sentence"]').text()).toContain('Someone');
        expect(wrapper.find('[data-testid="notification-avatar"]').text()).toBe('S');
    });

    it('counts what it is holding on the trigger', async () => {
        const wrapper = await menuShowing([aNotification({ id: 1 }), aNotification({ id: 2 })]);

        expect(wrapper.find('[data-testid="notifications-count"]').text()).toBe('2');
    });

    it('stays closed until the trigger is clicked', async () => {
        NotificationApi.index = vi.fn().mockResolvedValue([aNotification()]);

        const wrapper = mount(NotificationsMenu);
        await flushPromises();

        expect(wrapper.find('[data-testid="notifications-panel"]').exists()).toBe(false);
    });

    it('says so when there is nothing to show', async () => {
        const wrapper = await menuShowing([]);

        expect(wrapper.find('[data-testid="notifications-empty"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="notifications-count"]').exists()).toBe(false);
    });

    // Opening and closing must not re-derive rows that have not changed.
    it('reads each notification once no matter how often the panel is opened', async () => {
        const wrapper = await menuShowing([aNotification({ id: 1 }), aNotification({ id: 2 }), aNotification({ id: 3 })]);
        const trigger = wrapper.find('[data-testid="notifications-trigger"]');

        await trigger.trigger('click');
        await trigger.trigger('click');
        await trigger.trigger('click');

        expect(notificationSentence).toHaveBeenCalledTimes(3);
    });

    // The header is on every page, so a failing request must not take the whole layout with it.
    it('survives the endpoint failing', async () => {
        NotificationApi.index = vi.fn().mockRejectedValue(new Error('502'));

        const wrapper = mount(NotificationsMenu);
        await flushPromises();
        await wrapper.find('[data-testid="notifications-trigger"]').trigger('click');
        await flushPromises();

        expect(wrapper.find('[data-testid="notifications-empty"]').exists()).toBe(true);
    });
});
