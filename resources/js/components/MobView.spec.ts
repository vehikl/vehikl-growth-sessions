import {mount, Wrapper} from "@vue/test-utils";
import MobView from "./MobView.vue";
import userJson from '../../../tests/fixtures/User.json';

const testMob = {
    id: 123,
    topic: 'test',
    location: 'test',
    date: '01/01/2020',
    start_time: new Date().toDateString(),
    end_time: new Date().toDateString(),
    owner: userJson,
    attendees: [
        {
            avatar: 'lastAirBender.jpg',
            email: 'jack@bauer.com',
            id: 987,
            name: 'Cat',
            username: 'cat'
        },
        {
            avatar: 'lastAirBender.jpg',
            email: 'jack@bauer.com',
            id: 986,
            name: 'Dog',
            username: 'dog'
        }
    ],
    comments: [],
}

describe('MobView', () => {
    let wrapper: Wrapper<MobView>;

    beforeEach(() => {
        wrapper = mount(MobView, {propsData: {user: userJson, mobJson: testMob}});
    });

    describe('attendees section', () => {
        it('redirects to the attendees GitHub page', async () => {
            const attendeeComponents = wrapper.findAllComponents({ref: 'attendee'})

            attendeeComponents.wrappers.forEach((attendeeComponent, i) =>
                expect(attendeeComponent.element).toHaveAttribute('href', `https://github.com/${testMob.attendees[i].username}`)
            )
        })
    })
})
