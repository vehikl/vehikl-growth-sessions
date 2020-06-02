import {createLocalVue, mount, Wrapper} from '@vue/test-utils';
import WeekView from './WeekView.vue';
import flushPromises from 'flush-promises';
import socialsThisWeekJson from '../../../tests/fixtures/WeekSocials.json';
import {IUser} from '../types';
import VModal from 'vue-js-modal';
import {SocialMobApi} from '../services/SocialMobApi';
import {DateTime} from '../classes/DateTime';
import {WeekMobs} from '../classes/WeekMobs';
import {SocialMob} from '../classes/SocialMob';

const authUser: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    id: 987,
    name: 'Jack Bauer'
};

const localVue = createLocalVue();
localVue.use(VModal);
let socialsThisWeek: WeekMobs = new WeekMobs(socialsThisWeekJson);

describe('WeekView', () => {
    let wrapper: Wrapper<WeekView>;
    let thisWeeksMonday: string;
    let todayIsWednesday: string;
    let nextMonday: string;
    let previousWeeksMonday: string;

    beforeEach(async () => {
        todayIsWednesday = '2020-01-15';
        thisWeeksMonday = '2020-01-13';
        previousWeeksMonday = '2020-01-06';
        nextMonday = '2020-01-20';
        DateTime.setTestNow(todayIsWednesday);
        SocialMobApi.getAllMobsOfTheWeek = jest.fn().mockResolvedValue(socialsThisWeek);
        wrapper = mount(WeekView, {localVue});
        await flushPromises();
    });

    it('loads with the current week socials in display', () => {
        const topicsOfTheWeek = socialsThisWeek.allMobs.map((mob: SocialMob) => mob.topic);
        for (let topic of topicsOfTheWeek) {
            expect(wrapper.text()).toContain(topic);
        }
    });


    it('allows the user to view mobs of the previous week', async () => {
        wrapper.find('button.load-previous-week').trigger('click');
        await flushPromises();
        let sevenDaysInThePast = DateTime.parseByDate(todayIsWednesday).addDays(-7).toDateString();
        expect(SocialMobApi.getAllMobsOfTheWeek).toHaveBeenCalledWith(sevenDaysInThePast);
    });

    it('allows the user to view mobs of the next week', async () => {
        wrapper.find('button.load-next-week').trigger('click');
        await flushPromises();
        let sevenDaysInTheFuture = DateTime.parseByDate(todayIsWednesday).addDays(7).toDateString();
        expect(SocialMobApi.getAllMobsOfTheWeek).toHaveBeenCalledWith(sevenDaysInTheFuture);
    });

    it('shows only the current day in mobile devices', () => {
        window = Object.assign(window, {innerWidth: 300});

        let today = DateTime.today();
        let tomorrow = DateTime.today().addDays(1);
        expect(wrapper.find(`[weekDay=${today.weekDayString()}`).element).not.toHaveClass('hidden');
        expect(wrapper.find(`[weekDay=${tomorrow.weekDayString()}`).element).toHaveClass('hidden');
    });


    it('does not display the mob creation buttons for guests', async () => {
        expect(wrapper.find('button.create-mob').exists()).toBe(false);
    });

    describe('for an authenticated user', () => {
        beforeEach(async () => {
            wrapper = mount(WeekView, {localVue, propsData: {user: authUser}});
            await flushPromises();
        });

        it('allows the user to create a social mob', async () => {
            wrapper.find('button.create-mob').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('form.create-mob').exists()).toBe(true);
        });

        it('does not display the mob creation buttons for days in the past', async () => {
            const failPast = 'The create button was rendered in a past date';
            const failFuture = 'The create button was not rendered in a future date';
            expect(wrapper.find('[weekday=Monday] button.create-mob').exists(), failPast).toBe(false);
            expect(wrapper.find('[weekday=Tuesday] button.create-mob').exists(), failPast).toBe(false);

            expect(wrapper.find('[weekday=Wednesday] button.create-mob').exists(), failFuture).toBe(true);
            expect(wrapper.find('[weekday=Thursday] button.create-mob').exists(), failFuture).toBe(true);
            expect(wrapper.find('[weekday=Friday] button.create-mob').exists(), failFuture).toBe(true);
        });
    });
});
