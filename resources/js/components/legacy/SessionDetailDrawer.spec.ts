import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import SessionDetailDrawer from '@/components/legacy/SessionDetailDrawer.vue';
import { IUser } from '@/types';
import { DOMWrapper, mount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/lib/loginUrl', () => ({ loginUrl: () => '/login-from-here' }));

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
        date: '2099-06-18',
        start_time: '02:00 pm',
        end_time: '04:00 pm',
        location: 'Zoom',
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
        await closeButton
            .findAll('button')
            .find((b) => b.text().includes('CLOSE'))!
            .trigger('click');
        expect(closeButton.emitted('close')).toBeTruthy();

        const overlay = mountDrawer(makeSession(), vehiklUser);
        await overlay.find('[role="dialog"]').trigger('click');
        expect(overlay.emitted('close')).toBeTruthy();

        const escape = mountDrawer(makeSession(), vehiklUser);
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await escape.vm.$nextTick();
        expect(escape.emitted('close')).toBeTruthy();
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

    it('emits edit-requested and delete-requested for the owner', async () => {
        const session = makeSession();
        const wrapper = mountDrawer(session, owner);

        await wrapper.find('.update-button').trigger('click');
        await wrapper.find('.delete-button').trigger('click');

        expect((wrapper.emitted('edit-requested')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
        expect((wrapper.emitted('delete-requested')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
    });

    describe('invite link', () => {
        const shareUrl = 'https://growth.test/invitations/abc123';
        const guest: IUser = { id: 9, name: 'Client', avatar: '', github_nickname: 'client', is_vehikl_member: false };
        let writeText: ReturnType<typeof vi.fn>;

        // The server decides who may hand the invitation out and only then serializes share_url — see the GrowthSession
        // resource. Each viewer below is paired with the payload that viewer would actually receive.
        beforeEach(() => {
            writeText = vi.fn().mockResolvedValue(undefined);
            Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });
        });

        afterEach(() => {
            Reflect.deleteProperty(navigator, 'clipboard');
        });

        it('shows the share url, a copy control, and what the link does to the owner', () => {
            const wrapper = mountDrawer(makeSession({ share_url: shareUrl }), owner);

            expect(wrapper.find('.share-url').text()).toContain(shareUrl);
            expect(wrapper.find('.copy-share-url-button').exists()).toBe(true);
            expect(wrapper.text()).toContain('Anyone with this link can view this growth session and join after logging in');
        });

        it('shows the share url to a Vehikl member who is not the owner', () => {
            const wrapper = mountDrawer(makeSession({ share_url: shareUrl }), vehiklUser);

            expect(wrapper.find('.share-url').text()).toContain(shareUrl);
            expect(wrapper.find('.copy-share-url-button').exists()).toBe(true);
        });

        it('copies the share url to the clipboard', async () => {
            const wrapper = mountDrawer(makeSession({ share_url: shareUrl }), owner);

            await wrapper.find('.copy-share-url-button').trigger('click');

            expect(writeText).toHaveBeenCalledWith(shareUrl);
        });

        it('falls back to a hidden textarea when the clipboard api is unavailable, as it is over plain http', async () => {
            Reflect.deleteProperty(navigator, 'clipboard');
            const execCommand = vi.fn().mockReturnValue(true);
            Object.defineProperty(document, 'execCommand', { value: execCommand, configurable: true });

            const wrapper = mountDrawer(makeSession({ share_url: shareUrl }), owner);
            await wrapper.find('.copy-share-url-button').trigger('click');

            expect(execCommand).toHaveBeenCalledWith('copy');
            expect(wrapper.find('.copy-share-url-button').text()).toContain('Copied');

            Reflect.deleteProperty(document, 'execCommand');
        });

        it('hides the share url from an unlocked guest, whose payload carries none', () => {
            const wrapper = mountDrawer(makeSession({ share_url: undefined }), guest);

            expect(wrapper.find('.share-url').exists()).toBe(false);
            expect(wrapper.find('.copy-share-url-button').exists()).toBe(false);
            expect(wrapper.text()).not.toContain('Anyone with this link can view this growth session');
        });

        it('hides the share url from an anonymous visitor', () => {
            const wrapper = mountDrawer(makeSession({ share_url: undefined }));

            expect(wrapper.find('.share-url').exists()).toBe(false);
            expect(wrapper.find('.copy-share-url-button').exists()).toBe(false);
        });
    });

    describe('for an anonymous visitor', () => {
        // An invited guest arrives with the drawer already open; they must be able to log in from inside it,
        // rather than closing it and losing the session they were invited to.
        it('offers a login link in place of the join button', () => {
            const wrapper = mountDrawer(makeSession());

            expect(wrapper.find('.login-to-join-link').exists()).toBe(true);
            expect(wrapper.find('.login-to-join-link').attributes('href')).toBe('/login-from-here');
            expect(shown(wrapper.find('.join-button'))).toBe(false);
        });

        it('is not offered to someone already logged in', () => {
            expect(mountDrawer(makeSession(), vehiklUser).find('.login-to-join-link').exists()).toBe(false);
        });
    });
});
