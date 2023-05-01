import { mount, Wrapper } from '@vue/test-utils';
import GrowthSessionCard from './GrowthSessionCard.vue';
import { IUser } from '../types';
import { GrowthSessionApi } from '../services/GrowthSessionApi';
import { DateTime } from '../classes/DateTime';
import { GrowthSession } from '../classes/GrowthSession';

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

const baseGrowthSessionDataAttributes = {
    id: 0,
    owner: ownerOfTheGrowthSession,
    is_public: true,
    location: 'Somewhere over the rainbow',
    discord_channel_id: null,
    date: '2020-05-08',
    start_time: '03:30 pm',
    end_time: '05:00 pm',
    allow_watchers: true,
    title: 'Foobar',
    topic: 'The fundamentals of Foobar',
    attendee_limit: 41,
    attendees: [
        attendee
    ],
    watchers: [
        watcher
    ],
    comments: [],
    anydesk: {
        id: 1,
        name: 'TestoDesko',
        remote_desk_id: '123 456 789',
    }
};



describe('GrowthSessionCard', () => {
    let wrapper: Wrapper<GrowthSessionCard>;
    let growthSessionData: GrowthSession;

    beforeEach(() => {
        window.confirm = jest.fn();
        DateTime.setTestNow('2020-05-01 00:00:00.0000');
        growthSessionData = new GrowthSession(baseGrowthSessionDataAttributes);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData}})
    });

    it('displays the owner name', () => {
        expect(wrapper.text()).toContain(growthSessionData.owner.name);
    });

    it('displays the growth session topic', () => {
        expect(wrapper.text()).toContain(growthSessionData.topic);
    });

    it('displays the growth session location', () => {
        expect(wrapper.text()).toContain(growthSessionData.location);
    });

    it('displays the growth session anydesk name', () => {
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});

       expect(wrapper.text()).toContain(growthSessionData.anydesk?.name);
    });

    it('does not display the growth session anydesk name if there is none', () => {
        const noAnyDeskGrowthSession = new GrowthSession({...growthSessionData, anydesk: null });
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: noAnyDeskGrowthSession}})

        expect(wrapper.text()).not.toContain(growthSessionData.anydesk?.name);
    })

    it('displays the number of attendees', () => {
        expect(wrapper.find('.attendees-count').text()).toContain(growthSessionData.attendees.length);
    });

    it('displays the attendee limit', () => {
        expect(wrapper.find('.attendee-limit').text()).toContain(growthSessionData.attendee_limit);
    });

    it('does not display the attendee limit if there is none', () => {
        const noLimitGrowthSession = new GrowthSession({...growthSessionData, attendee_limit: null});
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: noLimitGrowthSession}})
        // TODO: is there a better way to make sure something isn't displayed in the card??
        expect(wrapper.find('.attendee-limit').element).not.toBeDefined();
    })

    it('does not display the join button to the owner of the growth session', () => {
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: ownerOfTheGrowthSession}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does not display the join button if you are already part of the growth session', () => {
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: attendee}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does not display the join button to guests', () => {
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does display the join button if you are authenticated and not part of the growth session', () => {
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});
        expect(wrapper.find('.join-button').element).toBeVisible();
    });

    it('does display the join button even if the growth session does not allow watchers', () => {
        growthSessionData.allow_watchers = false;
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});
        expect(wrapper.find('.join-button').element).toBeVisible();
    });

    it('allows a user to join a growth session', () => {
        window.open = jest.fn();
        GrowthSessionApi.join = jest.fn().mockImplementation(growthSession => growthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});
        wrapper.find('.join-button').trigger('click');
        expect(GrowthSessionApi.join).toHaveBeenCalledWith(growthSessionData);
    });

    it('allows a user to join a growth session as a watcher', () => {
        window.open = jest.fn();
        GrowthSessionApi.watch = jest.fn().mockImplementation(growthSession => growthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});

        expect(wrapper.find('.watch-button').element).toBeVisible();

        wrapper.find('.watch-button').trigger('click');
        expect(GrowthSessionApi.watch).toHaveBeenCalledWith(growthSessionData);
    });

    it('displays the join as watcher button even when the growth session is full', () => {
        window.open = jest.fn();
        GrowthSessionApi.watch = jest.fn().mockImplementation(growthSession => growthSession);
        growthSessionData.attendee_limit = 1;
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});

        expect(wrapper.find('.watch-button').element).toBeVisible();
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('allows a user to leave a growth session when they are a watcher', () => {
        GrowthSessionApi.leave = jest.fn().mockImplementation(growthSession => growthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: watcher}});

        expect(wrapper.find('.leave-button').element).toBeVisible()
        wrapper.find('.leave-button').trigger('click');

        expect(GrowthSessionApi.leave).toHaveBeenCalledWith(growthSessionData);
    })

    it('does not display watcher button when watchers are not allowed for a growth session', () => {
        window.open = jest.fn();
        GrowthSessionApi.watch = jest.fn().mockImplementation(growthSession => growthSession);
        growthSessionData.allow_watchers = false;

        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});

        expect(wrapper.find('.watch-button').element).not.toBeVisible();
    });

    it('allows a user to leave a growth session when they are an attendee', () => {
        GrowthSessionApi.leave = jest.fn().mockImplementation(growthSession => growthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: attendee}});
        wrapper.find('.leave-button').trigger('click');
        expect(GrowthSessionApi.leave).toHaveBeenCalledWith(growthSessionData);
    });

    it('prompts for confirmation when the owner clicks on the delete button', () => {
        window.confirm = jest.fn();
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: ownerOfTheGrowthSession}});
        wrapper.find('.delete-button').trigger('click');
        expect(window.confirm).toHaveBeenCalled();
    });

    it('deletes the growth session if the user clicks on the delete button and confirms', ()=> {
        GrowthSessionApi.delete = jest.fn();
        window.confirm = jest.fn().mockReturnValue(true);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: ownerOfTheGrowthSession}});
        wrapper.find('.delete-button').trigger('click');
        expect(GrowthSessionApi.delete).toHaveBeenCalledWith(growthSessionData);
    });

    it('does not display the edit button to the owner if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: ownerOfTheGrowthSession}});

        expect(wrapper.find('.delete-button').element).not.toBeVisible()
    });

    it('does not display the edit button to the owner if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: ownerOfTheGrowthSession}});

        expect(wrapper.find('.update-button').element).not.toBeVisible()
    });

    it('does not display the join button if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: outsider}});

        expect(wrapper.find('.join-button').element).not.toBeVisible()
    });

    it('does not display the leave button if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: attendee}});

        expect(wrapper.find('.leave-button').element).not.toBeVisible()
    });

    it('does not display the copy button if the user is not a vehikl member', () => {
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: nonVehiklMember}});
        expect(wrapper.find('.copy-button').element).not.toBeVisible();
    });

    it('does not display the growth session topic if the date of the growth session is in the past', () => {
        const oneDayAfterTheGrowthSession = DateTime.parseByDate(growthSessionData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheGrowthSession);
        wrapper = mount(GrowthSessionCard, {propsData: {growthSession: growthSessionData, user: attendee}});

        expect(wrapper.findComponent({ref: "growth-session-topic"}).element).not.toBeVisible();
    });

    it('Emits a copy-requested event when the copy button is clicked', () => {
        wrapper.find('.copy-button').trigger('click');

        expect(wrapper.emitted('copy-requested')).toBeTruthy();
    });

    describe('\"Add to Calendar\" link',() => {
        it('is visible', () => {
            expect(wrapper.findComponent({ref: 'add-to-calendar'}).element).toBeVisible();
        })

        it('has Google Calendar URI', () => {
            const addToCalendarLink = wrapper.findComponent({ref: 'add-to-calendar'}).element as HTMLAnchorElement;

            expect(addToCalendarLink.href).toContain("google.com/calendar");
        })
    })
});
