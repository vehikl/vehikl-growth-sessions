import {mount, Wrapper} from '@vue/test-utils';
import MobCard from './MobCard.vue';
import {ISocialMob} from '../types';

const mobData: ISocialMob = {
    id: 0,
    owner: {
        name: 'Jack Bauer',
        id: 1,
        email: 'jack@bauer.com',
        avatar: 'theLastAirBender.jpg'
    },
    start_time: '2020-05-08 20:20:00',
    topic: 'The fundamentals of Foobar',
    attendees: [
        {
            name: 'Alice',
            id: 2,
            email: 'alice@ecila.com',
            avatar: 'avatar.jpg'
        },
        {
            name: 'Rudolf',
            id: 3,
            email: 'red@nose.com',
            avatar: 'avatar.jpg'
        }
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

    it('displays the number of attendees', () => {
        expect(wrapper.find('.attendees-count').text()).toContain(mobData.attendees.length);
    });
});
