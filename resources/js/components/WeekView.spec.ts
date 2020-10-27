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
import {Nothingator} from '../classes/Nothingator';

const authUser: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    github_nickname: 'jackjack',
    id: 987,
    name: 'Jack Bauer'
};

const localVue = createLocalVue();
localVue.use(VModal);
let socialsThisWeek: WeekMobs = new WeekMobs(socialsThisWeekJson);

const metadataForSocialsFixture = {
    today: {date: '2020-01-15', weekday: 'Wednesday'},
    dayWithNoMobs: {date: '2020-01-16', weekday: 'Tuesday'}
};

const todayDate: string = metadataForSocialsFixture.today.date;

describe('WeekView', () => {
    let wrapper: Wrapper<WeekView>;

    beforeEach(async () => {
        DateTime.setTestNow(todayDate);
        SocialMobApi.getAllMobsOfTheWeek = jest.fn().mockResolvedValue(socialsThisWeek);
        SocialMobApi.join = jest.fn().mockImplementation(mob => mob);
        SocialMobApi.leave = jest.fn().mockImplementation(mob => mob);
        SocialMobApi.delete = jest.fn().mockImplementation(mob => mob);
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
        let sevenDaysInThePast = DateTime.parseByDate(todayDate).addDays(-7).toDateString();
        expect(SocialMobApi.getAllMobsOfTheWeek).toHaveBeenCalledWith(sevenDaysInThePast);
    });

    it('allows the user to view mobs of the next week', async () => {
        window.confirm = jest.fn();
        wrapper.find('button.load-next-week').trigger('click');
        await flushPromises();
        let sevenDaysInTheFuture = DateTime.parseByDate(todayDate).addDays(7).toDateString();
        expect(SocialMobApi.getAllMobsOfTheWeek).toHaveBeenCalledWith(sevenDaysInTheFuture);
    });

    it('shows only the current day in mobile devices', () => {
        window = Object.assign(window, {innerWidth: 300, open: () => null});

        let today = DateTime.today();
        let tomorrow = DateTime.today().addDays(1);
        expect(wrapper.find(`[weekDay=${today.weekDayString()}`).element).not.toHaveClass('hidden');
        expect(wrapper.find(`[weekDay=${tomorrow.weekDayString()}`).element).toHaveClass('hidden');
    });


    it('does not display the mob creation buttons for guests', async () => {
        expect(wrapper.find('button.create-mob').exists()).toBe(false);
    });

    it('if no mobs are available on that day, display a variation of nothing in different languages', async () => {
        const wordForNothing = 'A random nothing';
        Nothingator.random = jest.fn().mockReturnValue(wordForNothing);
        wrapper = mount(WeekView, {localVue});
        await flushPromises();

        expect(wrapper.find(`[weekDay=${metadataForSocialsFixture.dayWithNoMobs.weekday}`).text())
            .toContain(wordForNothing);
    });

    describe('for an authenticated user', () => {
        beforeEach(async () => {
            wrapper = mount(WeekView, {localVue, propsData: {user: authUser}});
            await flushPromises();
        });

        it('allows the user to create a growth session', async () => {
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
