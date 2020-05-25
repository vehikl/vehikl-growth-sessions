import {createLocalVue, mount, Wrapper} from '@vue/test-utils';
import WeekView from './WeekView.vue';
import flushPromises from 'flush-promises';
import socialsThisWeek from '../../../tests/fixtures/WeekSocials.json';
import {ISocialMob, IUser, IWeekMobs} from '../types';
import VModal from 'vue-js-modal';
import {SocialMobApi} from '../services/SocialMobApi';
import {DateTimeApi} from '../services/DateTimeApi';

const authUser: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    id: 987,
    name: 'Jack Bauer'
};

const localVue = createLocalVue();
localVue.use(VModal);

describe('WeekView', () => {
    let wrapper: Wrapper<WeekView>;
    let previousMonday: string;
    let today: string;
    let nextMonday: string;

    beforeEach(async () => {
        today = '2020-01-13';
        previousMonday = '2020-01-06';
        nextMonday = '2020-01-20';
        DateTimeApi.setTestNow(today);
        SocialMobApi.getAllMobsOfTheWeek = jest.fn().mockResolvedValue(socialsThisWeek);
        wrapper = mount(WeekView, {localVue});
        await flushPromises();
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

    it('allows the user to view mobs of the previous week', async () => {
        wrapper.find('button.load-previous-week').trigger('click');
        await flushPromises();
        expect(SocialMobApi.getAllMobsOfTheWeek).toHaveBeenCalledWith(previousMonday);
    });

    it('allows the user to view mobs of the next week', async () => {
        wrapper.find('button.load-next-week').trigger('click');
        await flushPromises();
        expect(SocialMobApi.getAllMobsOfTheWeek).toHaveBeenCalledWith(nextMonday);
    });

    it('shows only the current day in mobile devices', () => {
        window = Object.assign(window, {innerWidth: 300});

        let today = DateTimeApi.today();
        let tomorrow = DateTimeApi.today().addDays(1);
        expect(wrapper.find(`[weekDay=${today.weekDayString()}`).element).not.toHaveClass('hidden');
        expect(wrapper.find(`[weekDay=${tomorrow.weekDayString()}`).element).toHaveClass('hidden');
    });
});
