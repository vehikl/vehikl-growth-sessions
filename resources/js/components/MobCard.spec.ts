import {mount, Wrapper} from '@vue/test-utils';
import MobCard from './MobCard.vue';
import {ISocialMob, IUser} from '../types';

const ownerOfTheMob: IUser = {
    name: 'Jack Bauer',
    id: 1,
    email: 'jack@bauer.com',
    avatar: 'theLastAirBender.jpg'
};

const attendee: IUser = {
    name: 'Alice',
    id: 2,
    email: 'alice@ecila.com',
    avatar: 'avatar.jpg'
};

const outsider: IUser = {
    name: 'Rudolf',
    id: 3,
    email: 'red@nose.com',
    avatar: 'avatar.jpg'
};

const mobData: ISocialMob = {
    id: 0,
    owner: ownerOfTheMob,
    location: 'Somewhere over the rainbow',
    start_time: '2020-05-08 20:20:00',
    topic: 'The fundamentals of Foobar',
    attendees: [
        attendee
    ]
};

describe('MobCard', () => {
    let wrapper: Wrapper<MobCard>;
    beforeEach(() => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData}})
    });

    it('displays the owner name', () => {
        expect(wrapper.text()).toContain(mobData.owner.name);
    });

    it('displays the mob topic', () => {
        expect(wrapper.text()).toContain(mobData.topic);
    });

    it('displays the mob location', () => {
        expect(wrapper.text()).toContain(mobData.location);
    });

    it('displays the number of attendees', () => {
        expect(wrapper.find('.attendees-count').text()).toContain(mobData.attendees.length);
    });

    it('does not display the join button to the owner of the mob', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: ownerOfTheMob}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does not display the join button if you are already part of the mob', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: attendee}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does not display the join button to guests', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does display the join button if you are authenticated and not part of the mob', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: outsider}});
        expect(wrapper.find('.join-button').element).toBeVisible();
    });

    it('displays the mob location as an url if the location is a link', () => {
        const mobWithUrl: ISocialMob = {
            id: 0,
            owner: ownerOfTheMob,
            location: 'https://www.somewhere.com',
            start_time: '2020-05-08 20:20:00',
            topic: 'The fundamentals of Foobar',
            attendees: []
        };
        wrapper = mount(MobCard, {propsData: {socialMob: mobWithUrl, user: attendee}});

        expect(wrapper.find('a.location').exists()).toBe(true);
    });
});
