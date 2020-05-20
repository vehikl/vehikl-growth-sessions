import {mount, Wrapper} from '@vue/test-utils';
import WeekView from './WeekView.vue';
import MockAdapter from 'axios-mock-adapter';
import axios from 'axios';
import flushPromises from 'flush-promises';
import socialsThisWeek from '../../../tests/fixtures/WeekSocials.json';
import {ISocialMob} from '../types';

describe('WeekView', () => {
    let mockBackend: MockAdapter;
    let wrapper: Wrapper<WeekView>;

    beforeEach(async () => {
        mockBackend = new MockAdapter(axios);
        mockBackend.onGet('social_mob?filter=week').reply(200, socialsThisWeek);
        wrapper = mount(WeekView);
        await flushPromises();
    });

    afterEach(() => {
        mockBackend.restore();
    });

    it('loads with the current week socials in display', () => {
       const topicsForTheWeek = (socialsThisWeek as unknown as ISocialMob[]).map((social: ISocialMob) => social.topic);
       for (let topic of topicsForTheWeek) {
           expect(wrapper.text()).toContain(topic);
       }
    });
});
