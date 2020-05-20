import {createLocalVue, mount, Wrapper} from '@vue/test-utils';
import WeekView from './WeekView.vue';
import MockAdapter from 'axios-mock-adapter';
import axios from 'axios';
import flushPromises from 'flush-promises';
import socialsThisWeek from '../../../tests/fixtures/WeekSocials.json';
import {ISocialMob, IUser, IWeekMobs} from '../types';
import VModal from 'vue-js-modal';

const authUser: IUser = {
    avatar: "lastAirBender.jpg",
    email: "jack@bauer.com",
    id: 987,
    name: "Jack Bauer"
};

const localVue = createLocalVue();
localVue.use(VModal);

describe('WeekView', () => {
    let mockBackend: MockAdapter;
    let wrapper: Wrapper<WeekView>;

    beforeEach(async () => {
        mockBackend = new MockAdapter(axios);
        mockBackend.onGet('social_mob/week').reply(200, socialsThisWeek);
        wrapper = mount(WeekView, {localVue});
        await flushPromises();
    });

    afterEach(() => {
        mockBackend.restore();
    });

    it('loads with the current week socials in display', () => {
        const week = socialsThisWeek as unknown as IWeekMobs;
        const mobsOfTheWeek = Object.values(week).flat();
        const topicsOfTheWeek = mobsOfTheWeek.map((social: ISocialMob) => social.topic);
        for (let topic of topicsOfTheWeek) {
            expect(wrapper.text()).toContain(topic);
        }
    });

    it('allows an authenticated user to create a social mob', async () => {
        wrapper = mount(WeekView, {localVue, propsData: {user: authUser}});
        await flushPromises();
        wrapper.find('button.create-mob').trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('form.create-mob').exists()).toBe(true);
    });

    it('does not display the mob creation options for guests', async () => {
        expect(wrapper.find('button.create-mob').exists()).toBe(false);
    });
});
