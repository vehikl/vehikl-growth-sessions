import {mount, Wrapper} from "@vue/test-utils";
import MobView from "./MobView.vue";
import userJson from '../../../tests/fixtures/User.json';
import mobJson from '../../../tests/fixtures/SocialMobWithComments.json'
import {User} from "../classes/User";
import {IGrowthSession} from '../types';
import socialMobWithComments from '../../../tests/fixtures/SocialMobWithComments.json';
import {GrowthSession} from "../classes/GrowthSession";

const dummyMob: IGrowthSession = socialMobWithComments;

describe('MobView', () => {
    let wrapper: Wrapper<MobView>;

    beforeEach(() => {
        wrapper = mount(MobView, {propsData: {user: userJson, mobJson}});
    });

    it('redirects to the owners GitHub page when clicked on the profile', async () => {
        const ownerComponent = wrapper.findComponent({ref: 'owner-avatar-link'})

        expect(ownerComponent.element).toHaveAttribute('href', new User(mobJson.owner).githubURL)
    });

    describe('attendees section', () => {
        it('redirects to the attendees GitHub page when clicked on the profile', async () => {
            const attendeeComponents = wrapper.findAllComponents({ref: 'attendee'})

            attendeeComponents.wrappers.forEach((attendeeComponent, i) =>
                expect(attendeeComponent.element).toHaveAttribute('href', new User(mobJson.attendees[i]).githubURL)
            )
        })
    });

    it('can display the attendee limit', () => {
        let socialMob = new GrowthSession({...dummyMob, attendee_limit: 42});
        wrapper = mount(MobView, {propsData: {mobJson: socialMob}});
        expect(wrapper.text()).toContain('Attendee Limit');
        expect(wrapper.find('.attendee_limit').text()).toContain(socialMob.attendee_limit);
    });

    it('can hide the attendee limit', () => {
        let socialMob = new GrowthSession({...dummyMob, attendee_limit: null});
        wrapper = mount(MobView, {propsData: {mobJson: socialMob}});
        expect(wrapper.text()).not.toContain('Attendee Limit');
    });
});
