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
const inLine: IUser = { id: 4, name: 'Ada Byron', avatar: '', github_nickname: 'adab', is_vehikl_member: true };

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
        waitlist: [],
        waitlist_position: null,
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

    describe('the waitlist', () => {
        function fullSession(overrides: Partial<Record<string, unknown>> = {}) {
            return makeSession({ attendee_limit: 1, attendees: [attendee], ...overrides });
        }

        it('offers the queue in place of joining once the seats are gone', () => {
            const wrapper = mountDrawer(fullSession(), vehiklUser);

            expect(shown(wrapper.find('.join-button'))).toBe(false);
            expect(shown(wrapper.find('.join-waitlist-button'))).toBe(true);
            expect(wrapper.find('.join-waitlist-button').text()).toBe('Join waitlist');
        });

        it('offers joining rather than the queue while a seat remains', () => {
            const wrapper = mountDrawer(makeSession(), vehiklUser);

            expect(shown(wrapper.find('.join-button'))).toBe(true);
            expect(shown(wrapper.find('.join-waitlist-button'))).toBe(false);
        });

        it('enrols through the same call joining uses, and asks for a refresh', async () => {
            const session = fullSession();
            const joinSpy = vi.spyOn(session, 'join').mockResolvedValue(undefined);
            const wrapper = mountDrawer(session, vehiklUser);

            await wrapper.find('.join-waitlist-button').trigger('click');
            expect(joinSpy).toHaveBeenCalled();

            await flushPromises();
            expect(wrapper.emitted('refresh')).toBeTruthy();
        });

        it('offers somebody in line only the way out of it', () => {
            const wrapper = mountDrawer(fullSession({ waitlist: [vehiklUser], waitlist_position: 2 }), vehiklUser);

            expect(shown(wrapper.find('.join-waitlist-button'))).toBe(false);
            expect(shown(wrapper.find('.watch-button'))).toBe(false);
            expect(wrapper.find('.leave-button').text()).toBe('Leave waitlist');
        });

        it('lists everyone in line, in order, to anyone who can see the session', () => {
            const wrapper = mountDrawer(fullSession({ waitlist: [inLine, watcher] }), vehiklUser);

            const rows = wrapper.findAll('.waitlist-roster li');

            expect(wrapper.text()).toContain('WAITLIST (2)');
            expect(rows).toHaveLength(2);
            expect(rows[0].text()).toContain('Ada Byron');
            expect(rows[1].text()).toContain('Alan Turing');
        });

        it('says nothing about a waitlist when nobody is in line', () => {
            expect(mountDrawer(fullSession(), vehiklUser).text()).not.toContain('WAITLIST');
        });

        it('marks the queue up as an ordered list, so it is announced as one', () => {
            const wrapper = mountDrawer(fullSession({ waitlist: [inLine, watcher] }), vehiklUser);

            expect(wrapper.find('.waitlist-roster ol').exists()).toBe(true);
        });
    });

    /**
     * Attendees, the waitlist and the watchers are one roster in three roles, so they render
     * through the same component and everybody listed is followable - the drawer used to link
     * attendees alone and leave the other two reading as inert.
     */
    describe('the rosters', () => {
        function everyRoster() {
            return mountDrawer(makeSession({ attendee_limit: 1, attendees: [attendee], watchers: [watcher], waitlist: [inLine] }), vehiklUser);
        }

        it('links every member to their profile, whichever roster they stand in', () => {
            const links = everyRoster().findAll('a[href^="https://github.com/"]');

            expect(links.map((link) => link.attributes('href'))).toEqual([
                'https://github.com/grace',
                'https://github.com/adab',
                'https://github.com/alan',
            ]);
        });

        it('lists a member whose identity is withheld without pointing at a profile that is not theirs', () => {
            const guest: IUser = { id: 9, name: 'Guest', avatar: '', github_nickname: '', is_vehikl_member: false };
            const wrapper = mountDrawer(makeSession({ attendees: [guest] }), vehiklUser);

            expect(wrapper.text()).toContain('Guest');
            expect(wrapper.findAll('a[href^="https://github.com/"]')).toHaveLength(0);
        });

        it('shows all three together', () => {
            const wrapper = everyRoster();

            expect(wrapper.text()).toContain('ATTENDEES (1/1)');
            expect(wrapper.text()).toContain('WAITLIST (1)');
            expect(wrapper.text()).toContain('WATCHERS (1)');
        });
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

        it('hides the share url from a Vehikl member who is not the owner, whose payload carries none', () => {
            const wrapper = mountDrawer(makeSession({ share_url: undefined }), vehiklUser);

            expect(wrapper.find('.share-url').exists()).toBe(false);
            expect(wrapper.find('.copy-share-url-button').exists()).toBe(false);
        });

        it('copies the share url to the clipboard', async () => {
            const wrapper = mountDrawer(makeSession({ share_url: shareUrl }), owner);

            await wrapper.find('.copy-share-url-button').trigger('click');

            expect(writeText).toHaveBeenCalledWith(shareUrl);
        });

        it('falls back to a hidden textarea when the clipboard api is unavailable, as it is over plain http', async () => {
            Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
            const execCommand = vi.fn().mockReturnValue(true);
            Object.defineProperty(document, 'execCommand', { value: execCommand, configurable: true });

            const wrapper = mountDrawer(makeSession({ share_url: shareUrl }), owner);
            await wrapper.find('.copy-share-url-button').trigger('click');
            await flushPromises();

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

    describe('the series it belongs to', () => {
        it('is left alone for a session that stands on its own', () => {
            const wrapper = mountDrawer(makeSession(), vehiklUser);

            expect(wrapper.find('.session-series').exists()).toBe(false);
        });

        // The name travels with the session itself, so the header needs nothing looked up.
        it('names the series the session is filed under', () => {
            const wrapper = mountDrawer(makeSession({ series_name: 'Vue Deep Dive' }), vehiklUser);

            expect(wrapper.find('.session-series').text()).toContain('Vue Deep Dive');
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
