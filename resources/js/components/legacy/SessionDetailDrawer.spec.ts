import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import SessionDetailDrawer from '@/components/legacy/SessionDetailDrawer.vue';
import { IUser } from '@/types';
import { DOMWrapper, mount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const today = '2099-06-15';

const vehiklUser: IUser = { id: 987, name: 'Jack Bauer', avatar: '', github_nickname: 'jack', is_vehikl_member: true };
const owner: IUser = { id: 1, name: 'Ada Lovelace', avatar: '', github_nickname: 'ada', is_vehikl_member: true };
const attendee: IUser = { id: 2, name: 'Grace Hopper', avatar: '', github_nickname: 'grace', is_vehikl_member: true };
const watcher: IUser = { id: 3, name: 'Alan Turing', avatar: '', github_nickname: 'alan', is_vehikl_member: true };

function makeSession(overrides: Partial<Record<string, unknown>> = {}): GrowthSession {
    return new GrowthSession({
        id: 42,
        title: 'Deep Dive',
        topic: 'TDD patterns',
        topic_segments: [{ type: 'text', value: 'TDD patterns' }],
        date: '2099-06-18',
        start_time: '02:00 pm',
        end_time: '04:00 pm',
        location: 'Zoom',
        location_segments: [{ type: 'text', value: 'Zoom' }],
        is_public: true,
        allow_watchers: true,
        attendee_limit: 4,
        owner,
        attendees: [attendee],
        watchers: [],
        comments: [],
        discord_channel_id: null,
        anydesk: null,
        tags: [{ id: 1, name: 'testing' }],
        ...overrides,
    } as never);
}

function mountDrawer(session: GrowthSession, user?: IUser) {
    return mount(SessionDetailDrawer, {
        props: { growthSession: session, user },
        global: { stubs: { CommentList: true } },
        attachTo: document.body,
    });
}

function shown(w: DOMWrapper<Element>): boolean {
    return w.exists() && !(w.attributes('style') ?? '').includes('display: none');
}

describe('SessionDetailDrawer', () => {
    beforeEach(() => {
        DateTime.setTestNow(today);
    });

    afterEach(() => {
        DateTime.setTestNow(new Date().toISOString());
    });

    it('renders the title, owner, topic, tag and status', () => {
        const wrapper = mountDrawer(makeSession(), vehiklUser);

        expect(wrapper.find('#drawer-title').text()).toBe('Deep Dive');
        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.text()).toContain('TDD patterns');
        expect(wrapper.text()).toContain('testing');
        expect(wrapper.text()).toContain('UPCOMING');
    });

    it('lists attendees with the capacity count', () => {
        const wrapper = mountDrawer(makeSession(), vehiklUser);

        expect(wrapper.text()).toContain('ATTENDEES (1/4)');
        expect(wrapper.text()).toContain('Grace Hopper');
        expect(wrapper.find('a[href="https://github.com/grace"]').exists()).toBe(true);
    });

    it('hides the watchers section when there are none and shows it otherwise', () => {
        expect(mountDrawer(makeSession({ watchers: [] }), vehiklUser).text()).not.toContain('WATCHERS');

        const withWatchers = mountDrawer(makeSession({ watchers: [watcher] }), vehiklUser);
        expect(withWatchers.text()).toContain('WATCHERS (1)');
        expect(withWatchers.text()).toContain('Alan Turing');
    });

    it('links to the session mobtime room', () => {
        const wrapper = mountDrawer(makeSession(), vehiklUser);

        expect(wrapper.find('a[href*="mobtime.vehikl.com/vgs-"]').attributes('href')).toContain('vgs-42');
    });

    it('emits close from the close button, the overlay and the Escape key', async () => {
        const closeButton = mountDrawer(makeSession(), vehiklUser);
        await closeButton.find('button[aria-label="Close"]').trigger('click');
        expect(closeButton.emitted('close')).toBeTruthy();

        const overlay = mountDrawer(makeSession(), vehiklUser);
        await overlay.find('[role="dialog"]').trigger('click');
        expect(overlay.emitted('close')).toBeTruthy();

        const escape = mountDrawer(makeSession(), vehiklUser);
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await escape.vm.$nextTick();
        expect(escape.emitted('close')).toBeTruthy();
    });

    /**
     * The caller keeps the drawer mounted for its leave transition, so unmount is too late to
     * stop trapping focus — the keyboard has to stop reaching it the moment it starts closing.
     */
    describe('once it has started closing', () => {
        it('ignores a second Escape', async () => {
            const wrapper = mountDrawer(makeSession(), vehiklUser);

            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            await wrapper.vm.$nextTick();

            expect(wrapper.emitted('close')).toHaveLength(1);
        });

        it('lets Tab through rather than pulling focus back into the panel', async () => {
            const wrapper = mountDrawer(makeSession(), vehiklUser);
            const lastFocusable = wrapper.findAll('button').at(-1)!.element as HTMLElement;

            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            await wrapper.vm.$nextTick();
            lastFocusable.focus();

            const tab = new KeyboardEvent('keydown', { key: 'Tab', cancelable: true });
            document.dispatchEvent(tab);

            expect(tab.defaultPrevented).toBe(false);
        });

        it('ignores Escape after the edit button has handed off to the form', async () => {
            const wrapper = mountDrawer(makeSession(), owner);

            await wrapper.find('.update-button').trigger('click');
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            await wrapper.vm.$nextTick();

            expect(wrapper.emitted('edit-requested')).toHaveLength(1);
            expect(wrapper.emitted('close')).toBeFalsy();
        });
    });

    it('joins the session and asks for a refresh when the join button is clicked', async () => {
        const session = makeSession();
        const joinSpy = vi.spyOn(session, 'join').mockResolvedValue(undefined);
        const wrapper = mountDrawer(session, vehiklUser);

        const join = wrapper.find('.join-button');
        expect(shown(join)).toBe(true);

        await join.trigger('click');
        expect(joinSpy).toHaveBeenCalled();

        await flushPromises();
        expect(wrapper.emitted('refresh')).toBeTruthy();
    });

    describe('sharing', () => {
        afterEach(() => {
            Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
        });

        it('copies a link to this session and confirms the copy', async () => {
            const writeText = vi.fn().mockResolvedValue(undefined);
            Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });
            const session = makeSession();
            const wrapper = mountDrawer(session, vehiklUser);

            await wrapper.find('.share-button').trigger('click');
            await flushPromises();

            expect(writeText).toHaveBeenCalledWith(session.shareUrl);
            expect(wrapper.get('[role="status"]').text()).toContain('Link copied');
        });

        it('offers sharing to a visitor who cannot act on the session', () => {
            expect(mountDrawer(makeSession()).find('.share-button').exists()).toBe(true);
        });

        it('says so when the link could not be copied', async () => {
            Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
            Object.defineProperty(document, 'execCommand', { value: vi.fn().mockReturnValue(false), configurable: true });
            const wrapper = mountDrawer(makeSession(), vehiklUser);

            await wrapper.find('.share-button').trigger('click');
            await flushPromises();

            expect(wrapper.get('[role="status"]').text()).toContain('Could not copy');
        });
    });

    it('emits edit-requested and delete-requested for the owner', async () => {
        const session = makeSession();
        const wrapper = mountDrawer(session, owner);

        await wrapper.find('.update-button').trigger('click');
        await wrapper.find('.delete-button').trigger('click');

        expect((wrapper.emitted('edit-requested')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
        expect((wrapper.emitted('delete-requested')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
    });
});
