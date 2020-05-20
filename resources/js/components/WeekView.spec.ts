import {mount, Wrapper} from '@vue/test-utils';
import WeekView from './WeekView.vue';
import MockAdapter from 'axios-mock-adapter';
import axios from 'axios';
import flushPromises from 'flush-promises';
import socialsThisWeek from '../../../tests/fixtures/WeekSocials.json';
import {ISocialMob, IWeekMobs} from '../types';

describe('WeekView', () => {
    let mockBackend: MockAdapter;
    let wrapper: Wrapper<WeekView>;

    beforeEach(async () => {
        mockBackend = new MockAdapter(axios);
        mockBackend.onGet('social_mob/week').reply(200, socialsThisWeek);
        wrapper = mount(WeekView);
        await flushPromises();
    });

    afterEach(() => {
        mockBackend.restore();
    });

    it('loads with the current week socials in display', () => {
        const week = socialsThisWeek as unknown as IWeekMobs;
        const mobsOfTheWeek = [...week.monday, ...week.tuesday, ...week.wednesday, ...week.thursday, ...week.friday];
        const topicsOfTheWeek = mobsOfTheWeek.map((social: ISocialMob) => social.topic);
        for (let topic of topicsOfTheWeek) {
            expect(wrapper.text()).toContain(topic);
        }

    });
});
