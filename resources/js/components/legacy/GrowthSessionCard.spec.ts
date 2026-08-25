import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import GrowthSessionCard from '@/components/legacy/GrowthSessionCard.vue';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { IGrowthSession, IUser } from '@/types';
import { mount, type VueWrapper } from '@vue/test-utils';
import { vi } from 'vitest';

const ownerOfTheGrowthSession: IUser = {
    name: 'Jack Bauer',
    github_nickname: 'jackjack',
    id: 1,
    avatar: 'theLastAirBender.jpg',
    is_vehikl_member: true,
};

const attendee: IUser = {
    name: 'Alice',
    github_nickname: 'alisss',
    id: 2,
    avatar: 'avatar.jpg',
    is_vehikl_member: true,
};

const outsider: IUser = {
    name: 'Rudolf',
    github_nickname: 'deer123',
    id: 3,
    avatar: 'avatar.jpg',
    is_vehikl_member: false,
};

const watcher: IUser = {
    name: 'Uatu',
    github_nickname: 'uatu',
    id: 4,
    avatar: 'avatar.jpg',
    is_vehikl_member: true,
};

const nonVehiklMember: IUser = {
    name: 'James Bond',
    github_nickname: 'bond',
    id: 4,
    avatar: 'avatar.jpg',
    is_vehikl_member: false,
};

const baseGrowthSessionDataAttributes: IGrowthSession = {
    id: 0,
    owner: ownerOfTheGrowthSession,
    is_public: true,
    is_unlisted: false,
    location: 'Somewhere over the rainbow',
    location_segments: [{ type: 'text', value: 'Somewhere over the rainbow' }],
    discord_channel_id: null,
    date: '2020-05-08',
    start_time: '03:30 pm',
    end_time: '05:00 pm',
    allow_watchers: true,
    title: 'Foobar',
    topic: 'The fundamentals of Foobar',
    topic_segments: [{ type: 'text', value: 'The fundamentals of Foobar' }],
    attendee_limit: 41,
    attendees: [attendee],
    watchers: [watcher],
    waitlist: [],
    waitlist_position: null,
    comments: [],
    anydesk: {
        id: 1,
        name: 'TestoDesko',
        remote_desk_id: '123 456 789',
    },
    tags: [
        {
            id: 1,
            name: 'Test Tag #1',
        },
        {
            id: 2,
            name: 'Test Tag #2',
        },
    ],
};

describe('GrowthSessionCard', () => {
    let wrapper: VueWrapper;
    let growthSessionData: GrowthSession;

    beforeEach(() => {
        window.confirm = vi.fn();
        DateTime.setTestNow('2020-05-01 00:00:00.0000');
        growthSessionData = new GrowthSession(baseGrowthSessionDataAttributes);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData } });
    });

    it('displays the owner name', () => {
        expect(wrapper.text()).toContain(ownerOfTheGrowthSession.name);
    });

    it('displays the growth session location to attendees', () => {
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: attendee } });
        expect(wrapper.text()).toContain(growthSessionData.location);
    });

    it('renders whatever location_segments the backend sends, without re-deriving visibility itself', () => {
        const redactedGrowthSession = new GrowthSession({
            ...baseGrowthSessionDataAttributes,
            location: '< Join to see location >',
            location_segments: [{ type: 'text', value: '< Join to see location >' }],
        });
        wrapper = mount(GrowthSessionCard, { props: { growthSession: redactedGrowthSession, user: outsider } });
        expect(wrapper.text()).not.toContain(growthSessionData.location);
        expect(wrapper.text()).toContain('Join to see location');
    });

    it('displays the number of attendees', () => {
        expect(wrapper.find('.attendees-count').text()).toContain(growthSessionData.attendees.length);
    });

    it('displays the attendee limit', () => {
        expect(wrapper.find('.attendee-limit').text()).toContain(growthSessionData.attendee_limit);
    });

    it('does not display the attendee limit if there is none', () => {
        const noLimitGrowthSession = new GrowthSession({ ...growthSessionData, attendee_limit: null });
        wrapper = mount(GrowthSessionCard, { props: { growthSession: noLimitGrowthSession } });
        // TODO: is there a better way to make sure something isn't displayed in the card??
        expect(wrapper.find('.attendee-limit').exists()).toBe(false);
    });

    describe('when the attendee limit is reached', () => {
        function fullGrowthSession(): GrowthSession {
            return new GrowthSession({ ...baseGrowthSessionDataAttributes, attendee_limit: 1, attendees: [attendee] });
        }

        it('marks the capacity as full', () => {
            wrapper = mount(GrowthSessionCard, { props: { growthSession: fullGrowthSession(), user: outsider } });

            expect(wrapper.find('.attendees-count').classes()).toContain('gs-at-capacity');
        });

        it('does not mark the capacity as full while seats remain', () => {
            wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });

            expect(wrapper.find('.attendees-count').classes()).not.toContain('gs-at-capacity');
        });

        it('does not mark a limitless session as full', () => {
            const limitless = new GrowthSession({ ...baseGrowthSessionDataAttributes, attendee_limit: null });
            wrapper = mount(GrowthSessionCard, { props: { growthSession: limitless, user: outsider } });

            expect(wrapper.find('.attendees-count').classes()).not.toContain('gs-at-capacity');
        });

        // Whether a session reads as full is decided by the Board and handed down, so the
        // card only renders the flag. The rules behind it are covered in Board.spec.ts.
        it('replaces the join button with a waitlist button when the session is full', () => {
            wrapper = mount(GrowthSessionCard, { props: { growthSession: fullGrowthSession(), isFull: true, user: outsider } });

            expect(wrapper.find('.join-button')).not.toBeVisible();
            expect(wrapper.find('.join-waitlist-button')).toBeVisible();
            expect(wrapper.find('.join-waitlist-button').text()).toBe('Join waitlist');
        });

        it('does not stand a full indicator in the way of the waitlist button', () => {
            wrapper = mount(GrowthSessionCard, { props: { growthSession: fullGrowthSession(), isFull: true, user: outsider } });

            expect(wrapper.find('.full-indicator').exists()).toBe(false);
        });

        it('does not offer the waitlist unless the session is full', () => {
            wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });

            expect(wrapper.find('.join-waitlist-button')).not.toBeVisible();
            expect(wrapper.find('.join-button')).toBeVisible();
        });

        it('enrols the user through the same endpoint joining uses', () => {
            GrowthSessionApi.join = vi.fn().mockImplementation((growthSession) => growthSession);
            const session = fullGrowthSession();
            wrapper = mount(GrowthSessionCard, { props: { growthSession: session, isFull: true, user: outsider } });

            wrapper.find('.join-waitlist-button').trigger('click');

            expect(GrowthSessionApi.join).toHaveBeenCalledWith(session);
        });

        describe('to somebody already in line', () => {
            function queuedGrowthSession(): GrowthSession {
                return new GrowthSession({
                    ...baseGrowthSessionDataAttributes,
                    attendee_limit: 1,
                    attendees: [attendee],
                    waitlist: [outsider],
                    waitlist_position: 1,
                });
            }

            it('stops offering the queue they are already in', () => {
                wrapper = mount(GrowthSessionCard, { props: { growthSession: queuedGrowthSession(), isFull: true, user: outsider } });

                expect(wrapper.find('.join-waitlist-button')).not.toBeVisible();
                expect(wrapper.find('.full-indicator').exists()).toBe(false);
            });

            it('offers to take them out of the queue by name', () => {
                wrapper = mount(GrowthSessionCard, { props: { growthSession: queuedGrowthSession(), isFull: true, user: outsider } });

                expect(wrapper.find('.leave-button')).toBeVisible();
                expect(wrapper.find('.leave-button').text()).toBe('Leave waitlist');
            });
        });

        it('still offers spectating when the session is full', () => {
            wrapper = mount(GrowthSessionCard, { props: { growthSession: fullGrowthSession(), isFull: true, user: outsider } });

            expect(wrapper.find('.watch-button')).toBeVisible();
        });

        /**
         * `Join waitlist` and `Spectate` together need more room than the narrowest column the week
         * view renders, and the pair used to run off the edge of the card. happy-dom has no layout
         * to measure, so what is pinned here is the mechanism that keeps them inside it: the row
         * may break between actions, and no single action may grow past the width it is given.
         */
        it('keeps its actions inside the card whatever their labels say', () => {
            wrapper = mount(GrowthSessionCard, { props: { growthSession: fullGrowthSession(), isFull: true, user: outsider } });
            const actions = wrapper.find('.session-actions');
            const rendered = actions.findAll('button, span');

            expect(actions.classes()).toContain('flex-wrap');
            expect(rendered.length).toBeGreaterThan(0);
            rendered.forEach((action) => {
                expect(action.classes()).toContain('max-w-full');
                expect(action.classes()).toContain('truncate');
            });
        });
    });

    it('does not display the join button to the owner of the growth session', () => {
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: ownerOfTheGrowthSession } });
        expect(wrapper.find('.join-button')).not.toBeVisible();
    });

    it('does not display the join button if you are already part of the growth session', () => {
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: attendee } });
        expect(wrapper.find('.join-button')).not.toBeVisible();
    });

    it('does not display the join button to guests', () => {
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData } });
        expect(wrapper.find('.join-button')).not.toBeVisible();
    });

    it('does display the join button if you are authenticated and not part of the growth session', () => {
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });
        expect(wrapper.find('.join-button')).toBeVisible();
    });

    it('does display the join button even if the growth session does not allow watchers', () => {
        growthSessionData.allow_watchers = false;
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });
        expect(wrapper.find('.join-button')).toBeVisible();
    });

    it('allows a user to join a growth session', () => {
        window.open = vi.fn();
        GrowthSessionApi.join = vi.fn().mockImplementation((growthSession) => growthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });
        wrapper.find('.join-button').trigger('click');
        expect(GrowthSessionApi.join).toHaveBeenCalledWith(growthSessionData);
    });

    it('allows a user to join a growth session as a watcher', () => {
        window.open = vi.fn();
        GrowthSessionApi.watch = vi.fn().mockImplementation((growthSession) => growthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });

        expect(wrapper.find('.watch-button')).toBeVisible();

        wrapper.find('.watch-button').trigger('click');
        expect(GrowthSessionApi.watch).toHaveBeenCalledWith(growthSessionData);
    });

    it('displays the join as watcher button even when the growth session is full', () => {
        window.open = vi.fn();
        GrowthSessionApi.watch = vi.fn().mockImplementation((growthSession) => growthSession);
        growthSessionData.attendee_limit = 1;
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });

        expect(wrapper.find('.watch-button')).toBeVisible();
        expect(wrapper.find('.join-button')).not.toBeVisible();
    });

    it('allows a user to leave a growth session when they are a watcher', () => {
        GrowthSessionApi.leave = vi.fn().mockImplementation((growthSession) => growthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: watcher } });

        expect(wrapper.find('.leave-button')).toBeVisible();
        wrapper.find('.leave-button').trigger('click');

        expect(GrowthSessionApi.leave).toHaveBeenCalledWith(growthSessionData);
    });

    it('does not display watcher button when watchers are not allowed for a growth session', () => {
        window.open = vi.fn();
        GrowthSessionApi.watch = vi.fn().mockImplementation((growthSession) => growthSession);
        growthSessionData.allow_watchers = false;

        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });

        expect(wrapper.find('.watch-button')).not.toBeVisible();
    });

    it('allows a user to leave a growth session when they are an attendee', () => {
        GrowthSessionApi.leave = vi.fn().mockImplementation((growthSession) => growthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: attendee } });
        wrapper.find('.leave-button').trigger('click');
        expect(GrowthSessionApi.leave).toHaveBeenCalledWith(growthSessionData);
    });

    it('prompts for confirmation when the owner clicks on the delete button', () => {
        window.confirm = vi.fn();
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: ownerOfTheGrowthSession } });
        wrapper.find('.delete-button').trigger('click');
        expect(window.confirm).toHaveBeenCalled();
    });

    it('deletes the growth session if the user clicks on the delete button and confirms', () => {
        GrowthSessionApi.delete = vi.fn();
        window.confirm = vi.fn().mockReturnValue(true);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: ownerOfTheGrowthSession } });
        wrapper.find('.delete-button').trigger('click');
        expect(GrowthSessionApi.delete).toHaveBeenCalledWith(growthSessionData);
    });

    it('does not display the edit button to the owner if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: ownerOfTheGrowthSession } });

        expect(wrapper.find('.delete-button')).not.toBeVisible();
    });

    it('does not display the edit button to the owner if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: ownerOfTheGrowthSession } });

        expect(wrapper.find('.update-button')).not.toBeVisible();
    });

    it('does not display edit or delete after the session ends today', () => {
        DateTime.setTestNow(DateTime.parseByDateTime(growthSessionData.date, '06:00 pm').toISOString());
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: ownerOfTheGrowthSession } });

        expect(wrapper.find('.update-button')).not.toBeVisible();
        expect(wrapper.find('.delete-button')).not.toBeVisible();
    });

    it('does not display the join button if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: outsider } });

        expect(wrapper.find('.join-button')).not.toBeVisible();
    });

    it('does not display the leave button if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: attendee } });

        expect(wrapper.find('.leave-button')).not.toBeVisible();
    });

    it('does not display the copy button if the user is not a vehikl member', () => {
        wrapper = mount(GrowthSessionCard, { props: { growthSession: growthSessionData, user: nonVehiklMember } });
        expect(wrapper.find('.copy-button')).not.toBeVisible();
    });

    it('Emits a copy-requested event when the copy button is clicked', () => {
        wrapper.find('.copy-button').trigger('click');

        expect(wrapper.emitted('copy-requested')).toBeTruthy();
    });

    describe('\"Add to Calendar\" link', () => {
        it('is visible', () => {
            expect(wrapper.find('[aria-label=add-to-calendar]')).toBeVisible();
        });

        it('has Google Calendar URI', () => {
            const addToCalendarLink = wrapper.find('[aria-label=add-to-calendar]').element as HTMLAnchorElement;

            expect(addToCalendarLink.href).toContain('google.com/calendar');
        });
    });
});
