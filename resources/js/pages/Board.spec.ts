import discordChannelsJson from '@/../../tests/fixtures/Discord/Channels.json';
import rawGrowthSessionsThisWeek from '@/../../tests/fixtures/WeekGrowthSessions.json';
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { Nothingator } from '@/classes/Nothingator';
import { WeekGrowthSessions } from '@/classes/WeekGrowthSessions';
import DayView from '@/components/legacy/DayView.vue';
import GrowthSessionCard from '@/components/legacy/GrowthSessionCard.vue';
import SessionDetailDrawer from '@/components/legacy/SessionDetailDrawer.vue';
import WeekView from '@/components/legacy/WeekView.vue';
import Board from '@/pages/Board.vue';
import { AnydesksApi } from '@/services/AnydesksApi';
import { DiscordChannelApi } from '@/services/DiscordChannelApi';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { TagsApi } from '@/services/TagsApi';
import { IUser, IWeekGrowthSessions } from '@/types';
import { mount, type VueWrapper } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { vi } from 'vitest';

// Fixture JSON has no literal string types (e.g. `type: string`, not `type: 'text'`), unlike the
// discriminated unions the frontend types model - cast once here rather than at every use below.
const growthSessionsThisWeekJson = rawGrowthSessionsThisWeek as unknown as IWeekGrowthSessions;

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

const growthSessionsThisWeek: WeekGrowthSessions = new WeekGrowthSessions(growthSessionsThisWeekJson);

const metadataForGrowthSessionsFixture = {
    today: { date: '2020-01-15', weekday: 'Wednesday' },
    dayWithNoGrowthSessions: { date: '2020-01-16', weekday: 'Tuesday' },
    nextWeek: { date: '2020-01-22', weekday: 'Wednesday' },
};

const todayDate: string = metadataForGrowthSessionsFixture.today.date;

// Ziggy has no route table in unit tests; the login link's contents are covered in loginUrl.spec.ts.
vi.mock('@/lib/loginUrl', () => ({ loginUrl: () => '/login-from-here' }));

vi.mock('@laravel/echo-vue', () => ({
    default: vi.fn(),
    useEcho: vi.fn(),
}));

// happy-dom does not evaluate real media queries, so stub matchMedia to emulate a viewport.
function stubViewport(isDesktop: boolean) {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        configurable: true,
        value: (query: string) => ({
            matches: isDesktop,
            media: query,
            onchange: null,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            addListener: vi.fn(),
            removeListener: vi.fn(),
            dispatchEvent: vi.fn(),
        }),
    });
}

describe('Board', () => {
    let wrapper: VueWrapper;

    beforeEach(async () => {
        stubViewport(true); // default tests to a desktop-sized viewport
        DateTime.setTestNow(todayDate);
        GrowthSessionApi.getAllGrowthSessionsOfTheWeek = vi.fn().mockResolvedValue(growthSessionsThisWeek);
        GrowthSessionApi.join = vi.fn().mockImplementation((growthSession) => growthSession);
        GrowthSessionApi.leave = vi.fn().mockImplementation((growthSession) => growthSession);
        GrowthSessionApi.delete = vi.fn().mockImplementation((growthSession) => growthSession);
        DiscordChannelApi.index = vi.fn().mockResolvedValue(discordChannelsJson);
        DiscordChannelApi.occupied = vi.fn().mockResolvedValue([]);
        TagsApi.index = vi.fn().mockResolvedValue([
            { id: 1, name: 'foo' },
            { id: 2, name: 'bar' },
            { id: 3, name: 'baz' },
        ]);
        AnydesksApi.getAllAnyDesks = vi.fn();
        wrapper = mount(Board);
        await flushPromises();
    });

    afterEach(() => {
        window.history.replaceState({}, document.title, 'localhost');
        vi.restoreAllMocks();
    });

    // v-show toggles inline `display: none`; assert on that rather than isVisible(),
    // which does not detect v-show styling under happy-dom.
    function isViewShown(component: any): boolean {
        const view = wrapper.findComponent(component);
        return view.exists() && !(view.attributes('style') ?? '').includes('display: none');
    }

    it('loads with the current week growth sessions in display', () => {
        // Mounted as a guest, so only public sessions are shown.
        const publicTitlesOfTheWeek = growthSessionsThisWeek.allGrowthSessions
            .filter((growthSession: GrowthSession) => growthSession.is_public)
            .map((growthSession: GrowthSession) => growthSession.title);
        for (const title of publicTitlesOfTheWeek) {
            expect(wrapper.text()).toContain(title);
        }
    });

    it('allows the user to view growth sessions of the previous week', async () => {
        wrapper.find('button.load-previous-week').trigger('click');
        await flushPromises();
        const sevenDaysInThePast = DateTime.parseByDate(todayDate).addDays(-7).toDateString();
        expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(sevenDaysInThePast);
    });

    it('allows the user to view growth sessions of the next week', async () => {
        window.confirm = vi.fn();
        wrapper.find('button.load-next-week').trigger('click');
        await flushPromises();
        const sevenDaysInTheFuture = DateTime.parseByDate(todayDate).addDays(7).toDateString();
        expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(sevenDaysInTheFuture);
    });

    it('switches between day and week views with keyboard shortcuts', async () => {
        wrapper = mount(Board, { propsData: { user: authNonVehiklUser } });
        await flushPromises();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'w' }));
        await wrapper.vm.$nextTick();

        expect(isViewShown(WeekView)).toBe(true);
        expect(isViewShown(DayView)).toBe(false);

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'D' }));
        await wrapper.vm.$nextTick();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);
    });

    it('records view changes in the url while preserving the date', async () => {
        window.history.replaceState({}, '', `?date=${todayDate}`);
        wrapper.unmount();
        wrapper = mount(Board);
        await flushPromises();

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'w' }));
        await wrapper.vm.$nextTick();

        expect(window.location.search).toBe(`?date=${todayDate}&view=week`);
    });

    it('adopts the view in the query string on load', async () => {
        window.history.replaceState({}, '', '?view=week');
        wrapper.unmount();
        wrapper = mount(Board);
        await flushPromises();

        expect(isViewShown(WeekView)).toBe(true);
        expect(isViewShown(DayView)).toBe(false);
    });

    it('falls back to day view for an invalid view query value', async () => {
        window.history.replaceState({}, '', '?view=agenda');
        wrapper.unmount();
        wrapper = mount(Board);
        await flushPromises();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);
    });

    it('does not switch views while typing', async () => {
        wrapper = mount(Board, { propsData: { user: authNonVehiklUser } });
        await flushPromises();

        const searchInput = wrapper.find('input.search-input');
        await searchInput.trigger('keydown', { key: 'w' });
        await wrapper.vm.$nextTick();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);
    });

    it('allows guests to switch between day and week views', async () => {
        // The default beforeEach mounts the board with no user (a guest) on a desktop viewport.
        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);

        const viewButtons = wrapper.findAll('button[aria-keyshortcuts]');
        expect(viewButtons.length).toBe(2);
        viewButtons.forEach((button) => {
            expect(button.attributes('disabled')).toBeUndefined();
        });

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'w' }));
        await wrapper.vm.$nextTick();

        expect(isViewShown(WeekView)).toBe(true);
        expect(isViewShown(DayView)).toBe(false);
    });

    // A viewport that can change mid-test: `useMediaQuery` subscribes to the query's `change`
    // event, so the returned function flips `matches` and replays it to those subscribers.
    function stubResizableViewport(isDesktop: boolean): (desktop: boolean) => void {
        const listeners: Array<(event: { matches: boolean }) => void> = [];
        const state = { matches: isDesktop };

        Object.defineProperty(window, 'matchMedia', {
            writable: true,
            configurable: true,
            value: (query: string) => ({
                get matches() {
                    return state.matches;
                },
                media: query,
                onchange: null,
                addEventListener: (_type: string, listener: (event: { matches: boolean }) => void) => listeners.push(listener),
                removeEventListener: vi.fn(),
                addListener: (listener: (event: { matches: boolean }) => void) => listeners.push(listener),
                removeListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }),
        });

        return (desktop: boolean) => {
            state.matches = desktop;
            listeners.forEach((listener) => listener({ matches: desktop }));
        };
    }

    // Deliberate: a screen too narrow for the week view demotes the board to day, and it stays
    // demoted. Widening again must not yank the visitor back to a view they didn't re-ask for.
    it('keeps the day view after a small screen has demoted it, even once the screen widens', async () => {
        const resizeTo = stubResizableViewport(false);
        window.history.replaceState({}, '', '?view=week');
        wrapper.unmount();
        wrapper = mount(Board, { propsData: { user: authVehiklUser } });
        await flushPromises();

        expect(isViewShown(DayView)).toBe(true);

        resizeTo(true);
        await flushPromises();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);

        // ...but asking for the week view again now works, because the screen can show it.
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'w' }));
        await wrapper.vm.$nextTick();

        expect(isViewShown(WeekView)).toBe(true);
    });

    it('shows only the day view on small screens and disables week switching', async () => {
        stubViewport(false); // emulate a mobile-sized viewport
        window.history.replaceState({}, '', '?view=week');
        wrapper.unmount();
        wrapper = mount(Board, { propsData: { user: authVehiklUser } });
        await flushPromises();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);

        const viewButtons = wrapper.findAll('button[aria-keyshortcuts]');
        expect(viewButtons.length).toBe(2);
        viewButtons.forEach((button) => {
            expect(button.attributes('disabled')).toBeDefined();
        });

        // Even an authenticated user cannot switch to week view on a small screen.
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'w' }));
        await wrapper.vm.$nextTick();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);
    });

    it('does not display the growth session creation buttons for guests', async () => {
        expect(wrapper.find('button.create-growth-session').exists()).toBe(false);
    });

    it('if no growth sessions are available on that day, display a variation of nothing in different languages', async () => {
        const wordForNothing = 'A random nothing';
        Nothingator.random = vi.fn().mockReturnValue(wordForNothing);
        wrapper = mount(Board);
        await flushPromises();

        expect(wrapper.find(`[weekDay=${metadataForGrowthSessionsFixture.dayWithNoGrowthSessions.weekday}]`).text()).toContain(wordForNothing);
    });

    it('does not show visibility filter to unauthed user', () => {
        const visibilityFilters = wrapper.find('#visibility-filters');
        expect(visibilityFilters.exists()).toBeFalsy();
    });

    describe('Tag Filter', () => {
        beforeEach(async () => {
            wrapper = mount(Board, { propsData: { user: authNonVehiklUser } });
            await flushPromises();
        });

        it('renders the full list of available tags from the tags endpoint', async () => {
            const tagsFilter = wrapper.findComponent({ ref: 'growthSessionTags' });

            expect(tagsFilter.find('div#foo').text()).toBe('foo');
            expect(tagsFilter.find('div#bar').text()).toBe('bar');
            expect(tagsFilter.find('div#baz').text()).toBe('baz');
        });

        it('shows growth sessions that have any of the tags selected', async () => {
            let growthSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(growthSessions.length).toBe(5);

            const tagsFilter = wrapper.findComponent({ ref: 'growthSessionTags' });
            await tagsFilter.find('div#foo').trigger('click');

            growthSessions = wrapper.findAllComponents(GrowthSessionCard);

            expect(growthSessions.length).toBe(2);
        });
    });

    describe('for an authenticated non-vehikl user', () => {
        beforeEach(async () => {
            wrapper = mount(Board, { propsData: { user: authNonVehiklUser } });
            await flushPromises();
        });

        it('does not display the growth session creation buttons for authed non-vehikl users', async () => {
            expect(wrapper.find('button.create-growth-session').exists()).toBe(false);
        });

        it('does not render growth session filter for non vehikl users', async () => {
            const visibilityFilters = wrapper.find('#visibility-filters');
            expect(visibilityFilters.exists()).toBeFalsy();
        });
    });

    describe('for an authenticated vehikl user', () => {
        beforeEach(async () => {
            wrapper = mount(Board, { propsData: { user: authVehiklUser } });
            await flushPromises();
        });

        it('renders for authenticated vehikl users', async () => {
            const visibilityFilters = wrapper.find('#visibility-filters');
            expect(visibilityFilters.exists()).toBeTruthy();
        });

        it('allows an authed vehikl user to create a growth session', async () => {
            wrapper.find('button.create-growth-session').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('form.create-growth-session').exists()).toBe(true);
        });

        it('does not display the growth session creation buttons for days in the past', async () => {
            const failPast = 'The create button was rendered in a past date';
            const failFuture = 'The create button was not rendered in a future date';
            expect(wrapper.find('[weekday=Monday] button.create-growth-session').exists(), failPast).toBe(false);
            expect(wrapper.find('[weekday=Tuesday] button.create-growth-session').exists(), failPast).toBe(false);
            expect(wrapper.find('[weekday=Wednesday] button.create-growth-session').exists(), failFuture).toBe(true);
            expect(wrapper.find('[weekday=Thursday] button.create-growth-session').exists(), failFuture).toBe(true);
            expect(wrapper.find('[weekday=Friday] button.create-growth-session').exists(), failFuture).toBe(true);
        });

        it('shows a creation form pre-populated with data from some growth session when I click in some copy button', async () => {
            // The copy button only renders on sessions the authenticated user owns, so mount as one of the owners.
            const matchingSession = growthSessionsThisWeek.allGrowthSessions[0];
            wrapper = mount(Board, { propsData: { user: { ...matchingSession.owner, is_vehikl_member: true } } });
            await flushPromises();

            const title = matchingSession.title;
            const targetedGrowthSession = wrapper.findAllComponents(GrowthSessionCard).find((card) => card.find('h3').text() === title)!;

            expect(targetedGrowthSession.find('button.copy-button').exists()).toBe(true);
            await targetedGrowthSession.find('button.copy-button').trigger('click');

            const createForm = wrapper.find('form.create-growth-session');
            expect(createForm.exists()).toBe(true);

            expect(createForm.find<HTMLInputElement>('#is-public').element.checked).toBe(true);

            expect((wrapper.find('input#title').element as HTMLInputElement).value).toBe(title);
            expect((wrapper.find('textarea#topic').element as HTMLInputElement).value).toBe(matchingSession.topic);
        });

        describe('Visibility Filter', () => {
            it('it loads with "ALL" radio button selected', async () => {
                const radioButton = wrapper.find('#visibility-filters input[type=radio][name=filter-sessions]');

                expect((radioButton.element as HTMLInputElement).value).toBe('all');
                expect(radioButton).toBeChecked();
            });

            // The visibility rules themselves live in lib/sessionFilters.spec.ts; this only
            // proves the radio reaches the filter criteria.
            it('passes the chosen visibility through to the filter', async () => {
                const privateSessions = growthSessionsThisWeek.allGrowthSessions.filter((gs) => !gs.is_public);
                const publicSessions = growthSessionsThisWeek.allGrowthSessions.filter((gs) => gs.is_public);
                const isRendered = (title: string) => wrapper.findAllComponents(GrowthSessionCard).some((card) => card.text().includes(title));

                await wrapper.find('#visibility-filters input[type=radio][name=filter-sessions][id=private]').setValue(true);

                expect(privateSessions.every((gs) => isRendered(gs.title))).toBe(true);
                expect(publicSessions.some((gs) => isRendered(gs.title))).toBe(false);
            });
        });
    });

    describe('week persistence', () => {
        it('displays the current week of the day if no date value is provided in the url', () => {
            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(metadataForGrowthSessionsFixture.today.date);
        });

        // Reading each board parameter out of the url, writing it back on navigation, and
        // shifting from the url's date rather than today are covered directly in
        // composables/useBoardUrlState.spec.ts, which passes a fake window instead of
        // fighting happy-dom. These replace three tests that sat skipped here.
        it('re-fetches the week when the user navigates back through history', async () => {
            (GrowthSessionApi.getAllGrowthSessionsOfTheWeek as ReturnType<typeof vi.fn>).mockClear();
            window.history.replaceState({}, '', '?view=week');

            window.dispatchEvent(new PopStateEvent('popstate'));
            await flushPromises();

            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalled();
            expect(isViewShown(WeekView)).toBe(true);
        });
    });

    describe('Text Search Filter', () => {
        beforeEach(async () => {
            vi.useFakeTimers();
            wrapper = mount(Board, { propsData: { user: authNonVehiklUser } });
            await flushPromises();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('renders a search input field', () => {
            const searchInput = wrapper.find('input.search-input');
            expect(searchInput.exists()).toBeTruthy();
            expect(searchInput.attributes('placeholder')).toBe('Search sessions...');
        });

        // What the query matches against is covered in lib/sessionFilters.spec.ts; this only
        // proves the debounced query reaches the filter criteria.
        it('passes the debounced query through to the filter', async () => {
            const searchInput = wrapper.find('input.search-input');
            await searchInput.setValue('voluptas');

            vi.advanceTimersByTime(300);
            await flushPromises();

            const visibleSessions = wrapper.findAllComponents(GrowthSessionCard);

            // Sessions 1 and 3 have "voluptas" in their titles
            expect(visibleSessions.length).toBe(2);
            expect(visibleSessions.some((card) => card.text().includes('Voluptas vel distinctio'))).toBe(true);
            expect(visibleSessions.some((card) => card.text().includes('Maxime voluptas suscipit'))).toBe(true);
        });

        it('shows clear button when search has text', async () => {
            const searchInput = wrapper.find('input.search-input');

            // Initially no clear button
            expect(wrapper.find('button[aria-label="Clear search"]').exists()).toBe(false);

            // After typing, clear button appears
            await searchInput.setValue('test');
            await flushPromises();

            expect(wrapper.find('button[aria-label="Clear search"]').exists()).toBe(true);
        });

        it('clears search when clear button is clicked', async () => {
            const searchInput = wrapper.find('input.search-input');

            await searchInput.setValue('voluptas');
            vi.advanceTimersByTime(300);
            await flushPromises();

            let visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(2);

            // Click clear button
            const clearButton = wrapper.find('button[aria-label="Clear search"]');
            await clearButton.trigger('click');

            // Wait for debounce after clearing
            vi.advanceTimersByTime(300);
            await flushPromises();

            // All sessions should be visible again
            visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(5);
            expect((searchInput.element as HTMLInputElement).value).toBe('');
        });

        it('debounces search input to avoid excessive filtering', async () => {
            const searchInput = wrapper.find('input.search-input');

            // Type quickly without waiting
            await searchInput.setValue('v');
            await flushPromises();
            // Should still show all sessions since debounce hasn't triggered
            let visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(5);

            await searchInput.setValue('vo');
            await flushPromises();
            visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(5);

            await searchInput.setValue('voluptas');
            await flushPromises();
            visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(5);

            // Now wait for debounce to trigger
            vi.advanceTimersByTime(300);
            await flushPromises();

            // After debounce, filter should be applied
            visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(2); // Sessions with "voluptas" in title
        });
    });

    describe('sessions at capacity', () => {
        const memberA: IUser = { id: 501, name: 'Member A', github_nickname: 'a', avatar: '', is_vehikl_member: true };
        const memberB: IUser = { id: 502, name: 'Member B', github_nickname: 'b', avatar: '', is_vehikl_member: true };
        const sessionOwner: IUser = { id: 503, name: 'Session Owner', github_nickname: 'o', avatar: '', is_vehikl_member: true };

        const template = (growthSessionsThisWeekJson as Record<string, any[]>)['2020-01-13'][0];

        /** A week containing exactly one session, on today, with the given overrides. */
        async function boardShowing(sessionOverrides: Record<string, unknown>, user?: IUser) {
            const week: Record<string, unknown[]> = {};
            for (const date of Object.keys(growthSessionsThisWeekJson)) {
                week[date] = [];
            }
            week[todayDate] = [
                {
                    ...template,
                    date: todayDate,
                    end_time: '05:00 pm',
                    owner: sessionOwner,
                    attendees: [memberA, memberB],
                    watchers: [],
                    attendee_limit: 2,
                    ...sessionOverrides,
                },
            ];

            GrowthSessionApi.getAllGrowthSessionsOfTheWeek = vi.fn().mockResolvedValue(new WeekGrowthSessions(week as never));
            wrapper = mount(Board, { propsData: { user } });
            await flushPromises();

            return wrapper.findComponent(DayView).find('.join-waitlist-button');
        }

        // A session at capacity is no longer a dead end: the queue takes the Join button's place,
        // which is what now tells the viewer every seat is taken.
        it('offers the waitlist when every seat is taken', async () => {
            expect((await boardShowing({}, authVehiklUser)).exists()).toBe(true);
        });

        it('colours the capacity readout in the day view', async () => {
            await boardShowing({}, authVehiklUser);

            expect(wrapper.findComponent(DayView).find('.capacity-readout').classes()).toContain('gs-at-capacity');
        });

        it('colours the capacity readout in the week view', async () => {
            await boardShowing({}, authVehiklUser);

            expect(wrapper.findComponent(WeekView).find('.attendees-count').classes()).toContain('gs-at-capacity');
        });

        it('leaves the capacity readout uncoloured while seats remain', async () => {
            await boardShowing({ attendee_limit: 3 }, authVehiklUser);

            expect(wrapper.findComponent(DayView).find('.capacity-readout').classes()).not.toContain('gs-at-capacity');
            expect(wrapper.findComponent(WeekView).find('.attendees-count').classes()).not.toContain('gs-at-capacity');
        });

        it('does not offer the waitlist while seats remain', async () => {
            expect((await boardShowing({ attendee_limit: 3 }, authVehiklUser)).exists()).toBe(false);
        });

        it('does not offer the waitlist on a limitless session however many have joined', async () => {
            expect((await boardShowing({ attendee_limit: null }, authVehiklUser)).exists()).toBe(false);
        });

        it('does not offer the waitlist to the owner, who was never going to join', async () => {
            expect((await boardShowing({}, sessionOwner)).exists()).toBe(false);
        });

        it('does not offer the waitlist to someone already attending', async () => {
            expect((await boardShowing({}, memberA)).exists()).toBe(false);
        });

        it('does not offer the waitlist to guests, who cannot join at all', async () => {
            expect((await boardShowing({})).exists()).toBe(false);
        });

        it('does not offer the waitlist once the session has finished', async () => {
            // Later the same day, so the session is still on the selected day but already over.
            DateTime.setTestNow(`${todayDate} 18:00:00`);

            expect((await boardShowing({ end_time: '05:00 pm' }, authVehiklUser)).exists()).toBe(false);
        });
    });

    describe('the detail drawer', () => {
        async function openFirstSessionDetail() {
            await wrapper.findComponent(GrowthSessionCard).find('button[aria-label^="View details for"]').trigger('click');
            await flushPromises();
        }

        it('opens when a session card is clicked', async () => {
            expect(wrapper.findComponent(SessionDetailDrawer).exists()).toBe(false);

            await openFirstSessionDetail();

            expect(wrapper.findComponent(SessionDetailDrawer).exists()).toBe(true);
        });

        // Mounted bare, the drawer's leave is a hard close: v-if rips it out of the DOM with no
        // chance to animate. The transition is what keeps it mounted long enough to slide back
        // out, and its name has to match the gs-drawer-* rules in SessionDetailDrawer.vue. Vue
        // Test Utils stubs <Transition>, so only the wiring is observable here, not the motion.
        it('renders the drawer inside the gs-drawer transition so it animates out', async () => {
            await openFirstSessionDetail();

            const transition = wrapper.findComponent(SessionDetailDrawer).element.parentElement;

            expect(transition?.getAttribute('name')).toBe('gs-drawer');
        });

        it('closes when the drawer asks to be closed', async () => {
            await openFirstSessionDetail();

            wrapper.findComponent(SessionDetailDrawer).vm.$emit('close');
            await flushPromises();

            expect(wrapper.findComponent(SessionDetailDrawer).exists()).toBe(false);
        });
    });

    describe('deep-linked session detail', () => {
        // The invite link lands here: the board stays on screen and the session's drawer opens over it.
        const sessionOnAnotherDay = growthSessionsThisWeek.allGrowthSessions.find((gs) => gs.date === '2020-01-13')!;

        async function boardAt(search: string): Promise<VueWrapper> {
            window.history.replaceState({}, '', search);
            const board = mount(Board);
            await flushPromises();
            return board;
        }

        function drawer(): VueWrapper {
            return wrapper.findComponent(SessionDetailDrawer);
        }

        it('opens the drawer of the session named in the query string', async () => {
            wrapper = await boardAt(`?date=${todayDate}&session=${sessionOnAnotherDay.id}`);

            expect(drawer().exists()).toBe(true);
            expect((drawer().props() as { growthSession: GrowthSession }).growthSession.id).toBe(sessionOnAnotherDay.id);
        });

        it('moves the day view to the day the deep-linked session is on', async () => {
            wrapper = await boardAt(`?date=${todayDate}&session=${sessionOnAnotherDay.id}`);

            expect(wrapper.findComponent(DayView).props('selectedIndex')).toBe(
                growthSessionsThisWeek.weekDates.findIndex((day) => day.toDateString() === sessionOnAnotherDay.date),
            );
        });

        it('leaves the drawer closed when the query string names a session that is not visible', async () => {
            wrapper = await boardAt('?session=99999');

            expect(drawer().exists()).toBe(false);
        });

        it('records the open session in the url so the drawer can be shared and restored', async () => {
            wrapper.findComponent(DayView).vm.$emit('open-detail', sessionOnAnotherDay);
            await flushPromises();

            expect(new URLSearchParams(window.location.search).get('session')).toBe(String(sessionOnAnotherDay.id));
        });

        it('drops the session from the url when the drawer is closed', async () => {
            wrapper = await boardAt(`?date=${todayDate}&session=${sessionOnAnotherDay.id}`);

            drawer().vm.$emit('close');
            await flushPromises();

            expect(drawer().exists()).toBe(false);
            expect(new URLSearchParams(window.location.search).has('session')).toBe(false);
            expect(new URLSearchParams(window.location.search).get('date')).toBe(todayDate);
        });

        // The session belongs to the week being left behind, so carrying it into the next week's URL
        // would hand out a link that opens no drawer at all.
        it('drops the session when the board moves to another week', async () => {
            wrapper = await boardAt(`?date=${todayDate}&session=${sessionOnAnotherDay.id}`);

            await wrapper.find('.load-next-week').trigger('click');
            await flushPromises();

            expect(drawer().exists()).toBe(false);
            expect(new URLSearchParams(window.location.search).has('session')).toBe(false);
        });

        // Closing the drawer and moving the week are one gesture, so undoing it is one press of
        // Back. Two pushes here would leave the visitor pressing Back twice to get the week they left.
        it('spends a single history entry on closing the drawer and moving the week', async () => {
            wrapper = await boardAt(`?date=${todayDate}&session=${sessionOnAnotherDay.id}`);
            const pushState = vi.spyOn(window.history, 'pushState');

            await wrapper.find('.load-next-week').trigger('click');
            await flushPromises();

            expect(pushState).toHaveBeenCalledTimes(1);

            const pushedUrl = new URLSearchParams(pushState.mock.calls[0][2] as string);
            expect(pushedUrl.has('session')).toBe(false);
            expect(pushedUrl.get('date')).not.toBe(todayDate);

            pushState.mockRestore();
        });

        it('drops the session when the signed-in user changes', async () => {
            wrapper = await boardAt(`?date=${todayDate}&session=${sessionOnAnotherDay.id}`);

            await wrapper.setProps({ user: authVehiklUser });
            await flushPromises();

            expect(drawer().exists()).toBe(false);
            expect(new URLSearchParams(window.location.search).has('session')).toBe(false);
        });
    });

    describe('when the signed-in user changes', () => {
        it('re-fetches the week so private sessions do not linger after logging out', async () => {
            wrapper = mount(Board, { propsData: { user: authVehiklUser } });
            await flushPromises();

            (GrowthSessionApi.getAllGrowthSessionsOfTheWeek as ReturnType<typeof vi.fn>).mockClear();

            // Simulate an Inertia logout: the page swaps the user prop to null without remounting the board.
            await wrapper.setProps({ user: undefined });
            await flushPromises();

            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledTimes(1);
        });

        it('does not re-fetch when the same user is re-applied', async () => {
            wrapper = mount(Board, { propsData: { user: authVehiklUser } });
            await flushPromises();

            (GrowthSessionApi.getAllGrowthSessionsOfTheWeek as ReturnType<typeof vi.fn>).mockClear();

            await wrapper.setProps({ user: { ...authVehiklUser } });
            await flushPromises();

            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).not.toHaveBeenCalled();
        });

        it('hides private sessions from guests even when they are still cached client-side', async () => {
            const privateSession = growthSessionsThisWeek.allGrowthSessions.find((gs) => !gs.is_public);
            expect(privateSession).toBeTruthy(); // guard: the fixture must contain a private session

            wrapper = mount(Board, { propsData: { user: authVehiklUser } });
            await flushPromises();

            const showsPrivate = () => wrapper.findAllComponents(GrowthSessionCard).some((card) => card.text().includes(privateSession!.title));

            expect(showsPrivate()).toBe(true); // visible to the Vehikl user

            // Log out: the private session is still in the client cache, but must not be displayed.
            await wrapper.setProps({ user: undefined });
            await flushPromises();

            expect(showsPrivate()).toBe(false);
        });
    });
});
