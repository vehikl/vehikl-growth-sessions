import NotificationsMenu from '@/components/NotificationsMenu.vue';
import { notificationSegments } from '@/lib/notificationSentence';
import { NotificationApi } from '@/services/NotificationApi';
import type { INotification } from '@/types';
import { useEcho } from '@laravel/echo-vue';
import { mount, type VueWrapper } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { vi } from 'vitest';

vi.mock('@laravel/echo-vue', () => ({
    default: vi.fn(),
    useEcho: vi.fn(),
}));

const THE_USER = 7;

// Spied rather than stubbed: the sentences below are the real ones, we just want to count the work.
vi.mock('@/lib/notificationSentence', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/lib/notificationSentence')>();

    return { ...actual, notificationSegments: vi.fn(actual.notificationSegments) };
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
    const wrapper = mount(NotificationsMenu, { props: { userId: THE_USER } });
    await flushPromises();
    await wrapper.find('[data-testid="notifications-trigger"]').trigger('click');
    await flushPromises();
    return wrapper;
}

/** Hands a payload to the listener the menu gave Echo, the way a broadcast would arrive. */
async function broadcast(notification: INotification): Promise<void> {
    const calls = vi.mocked(useEcho).mock.calls;
    const listener = calls[calls.length - 1][2] as (payload: INotification) => void;

    listener(notification);
    await flushPromises();
}

function sentences(wrapper: VueWrapper): string[] {
    return wrapper.findAll('[data-testid="notification-sentence"]').map((row) => row.text());
}

describe('NotificationsMenu', () => {
    // Braces matter: a function returned from beforeEach is registered as a cleanup callback and called.
    beforeEach(() => {
        vi.mocked(notificationSegments).mockClear();
        vi.mocked(useEcho).mockClear();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('asks the endpoint for the notifications as soon as it mounts', async () => {
        NotificationApi.index = vi.fn().mockResolvedValue([]);

        mount(NotificationsMenu, { props: { userId: THE_USER } });
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

    // Three tiers, not two: emphasising the person, the session and the new value alike would put the
    // row back where it started, with nothing standing out because everything does.
    it('ranks the session title above the person and the new value, and both above the rest', async () => {
        const wrapper = await menuShowing([aNotification({ event_types: ['gs_location'] })]);

        // textContent rather than text(), which trims the spaces that separate the pieces.
        const pieces = wrapper.findAll('[data-testid="notification-sentence"] span');
        const weight = new Map(pieces.map((piece) => [piece.element.textContent, piece.classes()]));

        expect([...weight.keys()]).toEqual(['Ada', ' updated the location of ', 'Pairing on Vue', ', now ', 'at AnyDesk 12']);

        expect(weight.get('Pairing on Vue')).toEqual(expect.arrayContaining(['gs-text-strong', 'font-semibold']));
        expect(weight.get('Ada')).toEqual(['gs-text-strong']);
        expect(weight.get('at AnyDesk 12')).toEqual(['gs-text-strong']);
        expect(weight.get(' updated the location of ')).toEqual([]);
        expect(weight.get(', now ')).toEqual([]);
    });

    describe('how long ago it was', () => {
        const CREATED = '2026-08-17T18:32:00.000000Z';

        /** Freezes the clock before mounting, so the component's own interval is faked too. */
        function atTime(iso: string): void {
            vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval', 'Date'] });
            vi.setSystemTime(new Date(iso));
        }

        function elapsed(wrapper: VueWrapper): string {
            return wrapper.find('[data-testid="notification-created-at"]').text();
        }

        it('says how long ago the notification was', async () => {
            atTime('2026-08-17T18:35:00.000Z');

            expect(elapsed(await menuShowing([aNotification({ created_at: CREATED })]))).toBe('3 minutes ago');
        });

        // The label is deliberately imprecise, so the exact time has to survive somewhere.
        it('keeps the exact time on hover', async () => {
            atTime('2026-08-17T18:35:00.000Z');
            const wrapper = await menuShowing([aNotification({ created_at: CREATED })]);

            // Tests run in America/Toronto, so 18:32 UTC lands in the afternoon.
            expect(wrapper.find('[data-testid="notification-created-at"]').attributes('title')).toBe('Aug 17, 2:32 pm');
        });

        it('counts up on its own without anything else happening', async () => {
            atTime('2026-08-17T18:32:30.000Z');
            const wrapper = await menuShowing([aNotification({ created_at: CREATED })]);

            expect(elapsed(wrapper)).toBe('just now');

            await vi.advanceTimersByTimeAsync(60_000);
            expect(elapsed(wrapper)).toBe('1 minute ago');

            await vi.advanceTimersByTimeAsync(60_000);
            expect(elapsed(wrapper)).toBe('2 minutes ago');
        });

        it('moves up to coarser units as time passes', async () => {
            atTime('2026-08-17T18:32:30.000Z');
            const wrapper = await menuShowing([aNotification({ created_at: CREATED })]);

            await vi.advanceTimersByTimeAsync(59 * 60_000);
            expect(elapsed(wrapper)).toBe('59 minutes ago');

            await vi.advanceTimersByTimeAsync(60_000);
            expect(elapsed(wrapper)).toBe('1 hour ago');

            await vi.advanceTimersByTimeAsync(22 * 60 * 60_000);
            expect(elapsed(wrapper)).toBe('23 hours ago');

            await vi.advanceTimersByTimeAsync(60 * 60_000);
            expect(elapsed(wrapper)).toBe('1 day ago');
        });

        // An interval outlives the component that started it, and this one closes over a ref.
        it('stops its clock when the menu goes away', async () => {
            atTime('2026-08-17T18:35:00.000Z');
            const wrapper = await menuShowing([aNotification({ created_at: CREATED })]);

            expect(vi.getTimerCount()).toBe(1);

            wrapper.unmount();

            expect(vi.getTimerCount()).toBe(0);
        });
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
            'Ada cancelled Pairing on Vue, scheduled for Aug 20, 03:30 pm to 05:00 pm',
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

        const wrapper = mount(NotificationsMenu, { props: { userId: THE_USER } });
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

        expect(notificationSegments).toHaveBeenCalledTimes(3);
    });

    describe('live notifications', () => {
        it("listens on the signed-in user's own private channel", async () => {
            await menuShowing([]);

            expect(useEcho).toHaveBeenCalledWith(`notifications.${THE_USER}`, '.notification.created', expect.any(Function), [], 'private');
        });

        it('shows a notification that arrives while the menu is open', async () => {
            const wrapper = await menuShowing([aNotification({ id: 1 })]);

            await broadcast(aNotification({ id: 2, event_types: ['gs_deleted'] }));

            expect(sentences(wrapper)).toEqual([
                'Ada cancelled Pairing on Vue, scheduled for Aug 20, 03:30 pm to 05:00 pm',
                'Ada commented on Pairing on Vue',
            ]);
        });

        it('counts what arrived without the panel being open', async () => {
            NotificationApi.index = vi.fn().mockResolvedValue([]);
            const wrapper = mount(NotificationsMenu, { props: { userId: THE_USER } });
            await flushPromises();

            await broadcast(aNotification({ id: 1 }));

            expect(wrapper.find('[data-testid="notifications-count"]').text()).toBe('1');
        });

        // Echo replays on reconnect, and two rows sharing an id would collide on the list key.
        it('ignores a notification it is already showing', async () => {
            const wrapper = await menuShowing([aNotification({ id: 1 })]);

            await broadcast(aNotification({ id: 1 }));

            expect(wrapper.findAll('[data-testid="notification"]')).toHaveLength(1);
        });

        // The request is in flight for as long as the round trip takes, and anything raised in
        // that window would be lost if the response replaced the list instead of joining it.
        it('does not lose a notification that arrives before the first request answers', async () => {
            let answer: (notifications: INotification[]) => void = () => {};
            NotificationApi.index = vi.fn().mockReturnValue(new Promise((resolve) => (answer = resolve)));

            const wrapper = mount(NotificationsMenu, { props: { userId: THE_USER } });
            await broadcast(aNotification({ id: 99, event_types: ['gs_deleted'] }));

            answer([aNotification({ id: 1 })]);
            await flushPromises();
            await wrapper.find('[data-testid="notifications-trigger"]').trigger('click');

            expect(sentences(wrapper)).toEqual([
                'Ada cancelled Pairing on Vue, scheduled for Aug 20, 03:30 pm to 05:00 pm',
                'Ada commented on Pairing on Vue',
            ]);
        });

        // The cap is a window on the newest, not a ceiling that turns arrivals away: a full list
        // must still let a new notification in, and the one it pushes out is the oldest.
        it('makes room for a new notification by dropping the oldest', async () => {
            const numbered = (id: number) =>
                aNotification({ id, growth_session: { id, title: `Session ${id}`, location: null, date: null, start_time: null, end_time: null } });
            const wrapper = await menuShowing(Array.from({ length: 10 }, (_, index) => numbered(index + 1)));

            await broadcast(numbered(99));

            const shown = sentences(wrapper);
            expect(shown).toHaveLength(10);
            expect(shown[0]).toBe('Ada commented on Session 99');
            expect(shown).not.toContain('Ada commented on Session 10');
            expect(wrapper.find('[data-testid="notifications-count"]').text()).toBe('10');
        });
    });

    // The header is on every page, so a failing request must not take the whole layout with it.
    it('survives the endpoint failing', async () => {
        NotificationApi.index = vi.fn().mockRejectedValue(new Error('502'));

        const wrapper = mount(NotificationsMenu, { props: { userId: THE_USER } });
        await flushPromises();
        await wrapper.find('[data-testid="notifications-trigger"]').trigger('click');
        await flushPromises();

        expect(wrapper.find('[data-testid="notifications-empty"]').exists()).toBe(true);
    });
});
