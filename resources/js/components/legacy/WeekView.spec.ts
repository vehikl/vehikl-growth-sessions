import growthSessionsThisWeekJson from '@/../../tests/fixtures/WeekGrowthSessions.json';
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { WeekGrowthSessions } from '@/classes/WeekGrowthSessions';
import { IUser } from '@/types';
import { mount } from '@vue/test-utils';
import { vi } from 'vitest';
import GrowthSessionCard from './GrowthSessionCard.vue';
import WeekView from './WeekView.vue';

const authVehiklUser: IUser = {
    avatar: 'lastAirBender.jpg',
    github_nickname: 'jackjack',
    id: 987,
    name: 'Jack Bauer',
    is_vehikl_member: true,
};

const authNonVehiklUser: IUser = {
    avatar: 'yennefer.jpg',
    github_nickname: 'geraltofrivia',
    id: 1337,
    name: 'Geralt of Rivia',
    is_vehikl_member: false,
};

const todayDate = '2020-01-15';

function buildProps(user?: IUser, empty = false) {
    const week = new WeekGrowthSessions(growthSessionsThisWeekJson);
    const sessionsByDate: Record<string, GrowthSession[]> = {};
    for (const date of week.weekDates) {
        sessionsByDate[date.toDateString()] = empty ? [] : week.getSessionByDate(date);
    }
    return { weekDates: week.weekDates, sessionsByDate, user };
}

describe('WeekView (presentational week grid)', () => {
    beforeEach(() => DateTime.setTestNow(todayDate));
    afterEach(() => vi.restoreAllMocks());

    it('renders one column per week date, tagged with the weekDay attribute', () => {
        const props = buildProps();
        const wrapper = mount(WeekView, { propsData: props });
        for (const date of props.weekDates) {
            expect(wrapper.find(`[weekday="${date.weekDayString()}"]`).exists()).toBe(true);
        }
    });

    it('renders one GrowthSessionCard per session provided in sessionsByDate', () => {
        const props = buildProps(authVehiklUser);
        const wrapper = mount(WeekView, { propsData: props });
        const total = Object.values(props.sessionsByDate).reduce((sum, sessions) => sum + sessions.length, 0);
        expect(wrapper.findAllComponents(GrowthSessionCard).length).toBe(total);
    });

    it('shows the create (+) button for a vehikl member on non-past dates', () => {
        const wrapper = mount(WeekView, { propsData: buildProps(authVehiklUser) });
        expect(wrapper.find('button.create-growth-session').exists()).toBe(true);
    });

    it('does not show the create (+) button for guests', () => {
        const wrapper = mount(WeekView, { propsData: buildProps() });
        expect(wrapper.find('button.create-growth-session').exists()).toBe(false);
    });

    it('does not show the create (+) button for authed non-vehikl users', () => {
        const wrapper = mount(WeekView, { propsData: buildProps(authNonVehiklUser) });
        expect(wrapper.find('button.create-growth-session').exists()).toBe(false);
    });

    it('emits create with the column date when the + button is clicked', async () => {
        const wrapper = mount(WeekView, { propsData: buildProps(authVehiklUser) });
        await wrapper.find('button.create-growth-session').trigger('click');
        expect(wrapper.emitted('create')).toBeTruthy();
        expect(wrapper.emitted('create')![0][0]).toBeInstanceOf(DateTime);
    });

    it('shows a Nothingator empty state and no cards when a day has no sessions', () => {
        const wrapper = mount(WeekView, { propsData: buildProps(authVehiklUser, true) });
        expect(wrapper.findAllComponents(GrowthSessionCard).length).toBe(0);
        expect(wrapper.text()).toContain('...');
    });

    it('forwards card events up to the parent', async () => {
        const wrapper = mount(WeekView, { propsData: buildProps(authVehiklUser) });
        const card = wrapper.findComponent(GrowthSessionCard);
        const session = card.props('growthSession');

        card.vm.$emit('open-detail', session);
        card.vm.$emit('edit-requested', session);
        card.vm.$emit('copy-requested', session);
        card.vm.$emit('growth-session-updated');
        card.vm.$emit('delete-requested');
        await wrapper.vm.$nextTick();

        expect(wrapper.emitted('open-detail')![0][0]).toBe(session);
        expect(wrapper.emitted('edit-requested')![0][0]).toBe(session);
        expect(wrapper.emitted('copy-requested')![0][0]).toBe(session);
        expect(wrapper.emitted('refresh')!.length).toBe(2);
    });
});
