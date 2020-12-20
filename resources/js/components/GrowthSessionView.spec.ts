import {mount, Wrapper} from "@vue/test-utils";
import GrowthSessionView from "./GrowthSessionView.vue";
import userJson from '../../../tests/fixtures/User.json';
import growthSessionJson from '../../../tests/fixtures/GrowthSessionWithComments.json'
import {User} from "../classes/User";
import {IGrowthSession} from '../types';
import growthSessionWithComments from '../../../tests/fixtures/GrowthSessionWithComments.json';
import {GrowthSession} from "../classes/GrowthSession";

const dummyMob: IGrowthSession = growthSessionWithComments;

describe('GrowthSessionView', () => {
    let wrapper: Wrapper<GrowthSessionView>;

    beforeEach(() => {
        wrapper = mount(GrowthSessionView, {propsData: {user: userJson, mobJson: growthSessionJson}});
    });

    it('redirects to the owners GitHub page when clicked on the profile', async () => {
        const ownerComponent = wrapper.findComponent({ref: 'owner-avatar-link'})

        expect(ownerComponent.element).toHaveAttribute('href', new User(growthSessionJson.owner).githubURL)
    });

    describe('attendees section', () => {
        it('redirects to the attendees GitHub page when clicked on the profile', async () => {
            const attendeeComponents = wrapper.findAllComponents({ref: 'attendee'})

            attendeeComponents.wrappers.forEach((attendeeComponent, i) =>
                expect(attendeeComponent.element).toHaveAttribute('href', new User(growthSessionJson.attendees[i]).githubURL)
            )
        })
    });

    it('can display the attendee limit', () => {
        let socialMob = new GrowthSession({...dummyMob, attendee_limit: 42});
        wrapper = mount(GrowthSessionView, {propsData: {mobJson: socialMob}});
        expect(wrapper.text()).toContain('Attendee Limit');
        expect(wrapper.find('.attendee_limit').text()).toContain(socialMob.attendee_limit);
    });

    it('can hide the attendee limit', () => {
        let socialMob = new GrowthSession({...dummyMob, attendee_limit: null});
        wrapper = mount(GrowthSessionView, {propsData: {mobJson: socialMob}});
        expect(wrapper.text()).not.toContain('Attendee Limit');
    });
});
