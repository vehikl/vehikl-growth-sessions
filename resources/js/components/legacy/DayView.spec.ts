import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import DayView from '@/components/legacy/DayView.vue';
import { IUser } from '@/types';
import { DOMWrapper, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const today = '2099-06-15';

const vehiklUser: IUser = { id: 987, name: 'Jack Bauer', avatar: '', github_nickname: 'jack', is_vehikl_member: true };
const owner: IUser = { id: 1, name: 'Ada Lovelace', avatar: '', github_nickname: 'ada', is_vehikl_member: true };
const attendee: IUser = { id: 2, name: 'Grace Hopper', avatar: '', github_nickname: 'grace', is_vehikl_member: true };

const days = ['2099-06-15', '2099-06-16', '2099-06-17', '2099-06-18', '2099-06-19'].map((d) => DateTime.parseByDate(d));

function makeSession(overrides: Partial<Record<string, unknown>> = {}): GrowthSession {
    return new GrowthSession({
        id: 5,
        title: 'Refactoring Kata',
        topic: 'Improving legacy code',
        topic_segments: [{ type: 'text', value: 'Improving legacy code' }],
        date: '2099-06-16',
        start_time: '02:00 pm',
        end_time: '04:00 pm',
        location: 'Zoom',
        location_segments: [{ type: 'text', value: 'Zoom' }],
        is_public: true,
        allow_watchers: true,
        attendee_limit: 4,
        owner,
        attendees: [],
        watchers: [],
        comments: [],
        discord_channel_id: null,
        anydesk: null,
        tags: [],
        ...overrides,
    } as never);
}

function shown(w: DOMWrapper<Element>): boolean {
    return w.exists() && !(w.attributes('style') ?? '').includes('display: none');
}

describe('DayView', () => {
    beforeEach(() => {
        DateTime.setTestNow(today);
    });

    afterEach(() => {
        vi.useRealTimers();
        DateTime.setTestNow(new Date().toISOString());
    });

    it('renders one day-rail button per day and highlights the selected day', () => {
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 2, sessions: [], currentLabel: 'SAT', user: vehiklUser },
        });

        const railButtons = wrapper.find('.gs-col').findAll('button');
        expect(railButtons).toHaveLength(days.length);
        expect(railButtons[2].classes()).toContain('gs-accent-bg');
    });

    it('emits select-day with the day index when a rail button is clicked', async () => {
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 2, sessions: [], currentLabel: 'SAT', user: vehiklUser },
        });

        await wrapper.find('.gs-col').findAll('button')[3].trigger('click');

        expect(wrapper.emitted('select-day')?.[0]).toEqual([3]);
    });

    it('shows the empty state when there are no sessions and no create CTA', () => {
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [], currentLabel: 'THU', user: vehiklUser },
        });

        expect(wrapper.text()).toContain('No growth sessions scheduled for this day.');
    });

    it('renders the session title, owner and capacity', () => {
        const wrapper = mount(DayView, {
            props: {
                days,
                selectedIndex: 1,
                sessions: [makeSession({ attendees: [attendee] })],
                currentLabel: 'THU',
                user: vehiklUser,
            },
        });

        expect(wrapper.text()).toContain('Refactoring Kata');
        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.find('.capacity-readout').text()).toContain('1/4');
    });

    it('labels a live session next to its title', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(2099, 5, 16, 15));
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [makeSession()], currentLabel: 'THU', user: vehiklUser },
        });

        expect(wrapper.find('.live-session-label').text()).toBe('LIVE');
    });

    it('does not label an upcoming session as live', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(2099, 5, 16, 13));
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [makeSession()], currentLabel: 'THU', user: vehiklUser },
        });

        expect(wrapper.find('.live-session-label').exists()).toBe(false);
    });

    it('renders the full indicator only when the session id is in fullSessionIds', () => {
        const full = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [makeSession()], currentLabel: 'THU', fullSessionIds: [5], user: vehiklUser },
        });
        const notFull = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [makeSession()], currentLabel: 'THU', fullSessionIds: [], user: vehiklUser },
        });

        expect(full.find('.full-indicator').exists()).toBe(true);
        expect(notFull.find('.full-indicator').exists()).toBe(false);
    });

    it('emits open-detail with the session when the card overlay is clicked', async () => {
        const session = makeSession();
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [session], currentLabel: 'THU', user: vehiklUser },
        });

        await wrapper.find('button[aria-label^="View details"]').trigger('click');

        expect((wrapper.emitted('open-detail')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
    });

    it('shows the join button for an eligible user and emits join on click', async () => {
        const session = makeSession();
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [session], currentLabel: 'THU', user: vehiklUser },
        });

        const join = wrapper.find('.join-button');
        expect(shown(join)).toBe(true);

        await join.trigger('click');
        expect((wrapper.emitted('join')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
    });

    it('hides the join button from guests', () => {
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [makeSession()], currentLabel: 'THU', user: undefined },
        });

        expect(shown(wrapper.find('.join-button'))).toBe(false);
    });

    it('emits edit-requested and delete-requested for the owner', async () => {
        const session = makeSession();
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 1, sessions: [session], currentLabel: 'THU', user: owner },
        });

        await wrapper.find('.update-button').trigger('click');
        await wrapper.find('.delete-button').trigger('click');

        expect((wrapper.emitted('edit-requested')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
        expect((wrapper.emitted('delete-requested')?.[0]?.[0] as GrowthSession).id).toBe(session.id);
    });

    it('shows the create CTA and emits create when a member has no session today', async () => {
        const wrapper = mount(DayView, {
            props: { days, selectedIndex: 0, sessions: [], currentLabel: 'WED', user: vehiklUser },
        });

        const cta = wrapper.findAll('button').find((b) => b.text().includes("You're not in a session today"));
        expect(cta).toBeTruthy();

        await cta!.trigger('click');
        expect(wrapper.emitted('create')).toBeTruthy();
    });
});
