import discordChannelsJson from '@/../../tests/fixtures/Discord/Channels.json';
import growthSessionsThisWeekJson from '@/../../tests/fixtures/WeekGrowthSessions.json';
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { Nothingator } from '@/classes/Nothingator';
import { WeekGrowthSessions } from '@/classes/WeekGrowthSessions';
import DayView from '@/components/legacy/DayView.vue';
import GrowthSessionCard from '@/components/legacy/GrowthSessionCard.vue';
import WeekView from '@/components/legacy/WeekView.vue';
import Board from '@/pages/Board.vue';
import { AnydesksApi } from '@/services/AnydesksApi';
import { DiscordChannelApi } from '@/services/DiscordChannelApi';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { TagsApi } from '@/services/TagsApi';
import { IUser } from '@/types';
import { mount, Wrapper } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { vi } from 'vitest';

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

let growthSessionsThisWeek: WeekGrowthSessions = new WeekGrowthSessions(growthSessionsThisWeekJson);

const metadataForGrowthSessionsFixture = {
    today: { date: '2020-01-15', weekday: 'Wednesday' },
    dayWithNoGrowthSessions: { date: '2020-01-16', weekday: 'Tuesday' },
    nextWeek: { date: '2020-01-22', weekday: 'Wednesday' },
};

const todayDate: string = metadataForGrowthSessionsFixture.today.date;

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
    let wrapper: Wrapper<Board>;

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
        const titlesOfTheWeek = growthSessionsThisWeek.allGrowthSessions.map((growthSession: GrowthSession) => growthSession.title);
        for (let title of titlesOfTheWeek) {
            expect(wrapper.text()).toContain(title);
        }
    });

    it('allows the user to view growth sessions of the previous week', async () => {
        wrapper.find('button.load-previous-week').trigger('click');
        await flushPromises();
        let sevenDaysInThePast = DateTime.parseByDate(todayDate).addDays(-7).toDateString();
        expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(sevenDaysInThePast);
    });

    it('allows the user to view growth sessions of the next week', async () => {
        window.confirm = vi.fn();
        wrapper.find('button.load-next-week').trigger('click');
        await flushPromises();
        let sevenDaysInTheFuture = DateTime.parseByDate(todayDate).addDays(7).toDateString();
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

    it('does not switch views while typing', async () => {
        wrapper = mount(Board, { propsData: { user: authNonVehiklUser } });
        await flushPromises();

        const searchInput = wrapper.find('input.search-input');
        await searchInput.trigger('keydown', { key: 'w' });
        await wrapper.vm.$nextTick();

        expect(isViewShown(DayView)).toBe(true);
        expect(isViewShown(WeekView)).toBe(false);
    });

    it('shows guests the week view and disables view switching until signed in', async () => {
        // The default beforeEach mounts the board with no user (a guest) on a desktop viewport.
        expect(isViewShown(WeekView)).toBe(true);
        expect(isViewShown(DayView)).toBe(false);

        const viewButtons = wrapper.findAll('button[aria-keyshortcuts]');
        expect(viewButtons.length).toBe(2);
        viewButtons.forEach((button) => {
            expect(button.attributes('disabled')).toBeDefined();
        });

        // Keyboard shortcuts should not switch the view for guests.
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'd' }));
        await wrapper.vm.$nextTick();

        expect(isViewShown(WeekView)).toBe(true);
        expect(isViewShown(DayView)).toBe(false);
    });

    it('shows only the day view on small screens and disables week switching', async () => {
        stubViewport(false); // emulate a mobile-sized viewport
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
            let visibilityFilters = wrapper.find('#visibility-filters');
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
            const targetedGrowthSession = wrapper.findComponent(GrowthSessionCard);

            const title = targetedGrowthSession.find('h3').text();
            const matchingSession = growthSessionsThisWeek.allGrowthSessions.find((gs) => gs.title === title)!;

            expect(targetedGrowthSession.find('button.copy-button').exists()).toBe(true);
            await targetedGrowthSession.find('button.copy-button').trigger('click');

            let createForm = wrapper.find('form.create-growth-session');
            expect(createForm.exists()).toBe(true);

            expect(createForm.find('#is-public').element.checked).toBe(true);

            expect((wrapper.find('input#title').element as HTMLInputElement).value).toBe(title);
            expect((wrapper.find('textarea#topic').element as HTMLInputElement).value).toBe(matchingSession.topic);
        });

        describe('Visibility Filter', () => {
            it('it loads with "ALL" radio button selected', async () => {
                let radioButton = wrapper.find('#visibility-filters input[type=radio][name=filter-sessions]');

                expect(radioButton.element.value).toBe('all');
                expect(radioButton).toBeChecked();
            });

            it('shows both public and one private growth sessions on the page', async () => {
                const allGrowthSessionsThisWeek = growthSessionsThisWeek.allGrowthSessions;

                allGrowthSessionsThisWeek.forEach((growthSession) => {
                    const isTitleBeingRendered = wrapper
                        .findAllComponents(GrowthSessionCard)
                        .some((card) => card.text().includes(growthSession.title));

                    expect(isTitleBeingRendered).toEqual(true);
                });
            });

            it('shows only private growth sessions if the private filter is enabled', async () => {
                const privateGrowthSessionsThisWeek = growthSessionsThisWeek.allGrowthSessions.filter(function (growthSession) {
                    return !growthSession.is_public;
                });
                const publicGrowthSessionsThisWeek = growthSessionsThisWeek.allGrowthSessions.filter((gs) => gs.is_public);

                let radioButton = wrapper.find('#visibility-filters input[type=radio][name=filter-sessions][id=private]');
                await radioButton.setChecked();

                privateGrowthSessionsThisWeek.forEach((growthSession) => {
                    const isTitleBeingRendered = wrapper
                        .findAllComponents(GrowthSessionCard)
                        .some((card) => card.text().includes(growthSession.title));

                    expect(isTitleBeingRendered).toEqual(true);
                });

                publicGrowthSessionsThisWeek.forEach((publicGrowthSession) => {
                    const isTitleBeingRendered = wrapper
                        .findAllComponents(GrowthSessionCard)
                        .some((card) => card.text().includes(publicGrowthSession.title));

                    expect(isTitleBeingRendered).toEqual(false);
                });
            });

            it('shows only public growth sessions if the public filter is enabled', async () => {
                const privateGrowthSessionsThisWeek = growthSessionsThisWeek.allGrowthSessions.filter(function (growthSession) {
                    return !growthSession.is_public;
                });
                const publicGrowthSessionsThisWeek = growthSessionsThisWeek.allGrowthSessions.filter((gs) => gs.is_public);

                let radioButton = wrapper.find('#visibility-filters input[type=radio][name=filter-sessions][id=public]');
                await radioButton.setChecked();

                privateGrowthSessionsThisWeek.forEach((growthSession) => {
                    const isTitleBeingRendered = wrapper
                        .findAllComponents(GrowthSessionCard)
                        .some((card) => card.text().includes(growthSession.title));

                    expect(isTitleBeingRendered).toEqual(false);
                });

                publicGrowthSessionsThisWeek.forEach((publicGrowthSession) => {
                    const isTitleBeingRendered = wrapper
                        .findAllComponents(GrowthSessionCard)
                        .some((card) => card.text().includes(publicGrowthSession.title));

                    expect(isTitleBeingRendered).toEqual(true);
                });
            });
        });
    });

    describe('week persistence', () => {
        it('displays the current week of the day if no date value is provided in the url', () => {
            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(metadataForGrowthSessionsFixture.today.date);
        });

        it.skip('displays the growth sessions of the week of the date provided in the query string if it exists', () => {
            // TODO: Investigate how to use url with happy-dom
            window.history.pushState({}, 'sometitle', `?date=${metadataForGrowthSessionsFixture.nextWeek.date}`);
            wrapper = mount(Board);
            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(metadataForGrowthSessionsFixture.nextWeek.date);
        });

        it.skip('updates the query string for the date whenever the user navigates the weeks', async () => {
            // TODO: Investigate how to use url with happy-dom
            await wrapper.find('button[aria-label="Load next week"]').trigger('click');
            await flushPromises();

            const urlParameters: URLSearchParams = new URLSearchParams(window.location.search);
            expect(urlParameters.get('date')).toEqual(metadataForGrowthSessionsFixture.nextWeek.date);
        });

        it.skip('properly display the growth sessions of the day from the query string when the user navigates back in history', async () => {
            // TODO: Investigate how to use url with happy-dom
            window.history.pushState({}, 'sometitle', `?date=${metadataForGrowthSessionsFixture.nextWeek.date}`);
            wrapper = mount(Board);
            await flushPromises();
            GrowthSessionApi.getAllGrowthSessionsOfTheWeek = vi.fn();

            window.history.back = () => {
                console.error = vi.fn();
                const fakeMockEvent = {} as unknown as PopStateEvent;
                window.onpopstate!(fakeMockEvent);
            };

            window.history.back();
            await flushPromises();

            expect(GrowthSessionApi.getAllGrowthSessionsOfTheWeek).toHaveBeenCalledWith(metadataForGrowthSessionsFixture.nextWeek.date);
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

        it('shows all sessions when search is empty', async () => {
            let growthSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(growthSessions.length).toBe(5);
        });

        it('filters sessions by title', async () => {
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

        it('filters sessions by topic/description', async () => {
            const searchInput = wrapper.find('input.search-input');
            await searchInput.setValue('laborum');

            vi.advanceTimersByTime(300);
            await flushPromises();

            const visibleSessions = wrapper.findAllComponents(GrowthSessionCard);

            // Session 2 has "laborum" in its topic; searching by topic still matches it
            expect(visibleSessions.length).toBe(1);
        });

        it('filters sessions by owner/host name', async () => {
            const searchInput = wrapper.find('input.search-input');
            await searchInput.setValue('Tamia');

            vi.advanceTimersByTime(300);
            await flushPromises();

            const visibleSessions = wrapper.findAllComponents(GrowthSessionCard);

            // Sessions 4 and 5 are owned by "Tamia Thompson"
            expect(visibleSessions.length).toBe(2);
            expect(visibleSessions.every((card) => card.text().includes('Tamia Thompson'))).toBe(true);
        });

        it('filters sessions by attendee name', async () => {
            const searchInput = wrapper.find('input.search-input');
            await searchInput.setValue('Alejandro');

            vi.advanceTimersByTime(300);
            await flushPromises();

            const visibleSessions = wrapper.findAllComponents(GrowthSessionCard);

            // Session 2 has "Alejandro Rivera" as an attendee
            expect(visibleSessions.length).toBe(1);
        });

        it('performs case-insensitive search', async () => {
            const searchInput = wrapper.find('input.search-input');

            // Test with uppercase
            await searchInput.setValue('VOLUPTAS');
            vi.advanceTimersByTime(300);
            await flushPromises();

            let visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(2);

            // Test with mixed case
            await searchInput.setValue('StEpHaN');
            vi.advanceTimersByTime(300);
            await flushPromises();

            visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            // Session 1 is owned by "Stephan Klocko"
            expect(visibleSessions.length).toBe(1);
            expect(visibleSessions[0].text()).toContain('Stephan Klocko');
        });

        it('combines search with tag filter', async () => {
            const searchInput = wrapper.find('input.search-input');
            const tagsFilter = wrapper.findComponent({ ref: 'growthSessionTags' });

            // First apply tag filter (foo tag)
            await tagsFilter.find('div#foo').trigger('click');
            await flushPromises();

            let visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            // Sessions 1 and 3 have the 'foo' tag
            expect(visibleSessions.length).toBe(2);

            // Then apply search filter for "Stephan" (only session 1 has this owner)
            await searchInput.setValue('Stephan');
            vi.advanceTimersByTime(300);
            await flushPromises();

            visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            // Only session 1 matches both filters (has 'foo' tag AND owned by Stephan)
            expect(visibleSessions.length).toBe(1);
            expect(visibleSessions[0].text()).toContain('Stephan Klocko');
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

        it('shows no sessions when search matches nothing', async () => {
            const searchInput = wrapper.find('input.search-input');
            await searchInput.setValue('nonexistent search term that matches nothing');

            vi.advanceTimersByTime(300);
            await flushPromises();

            const visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
            expect(visibleSessions.length).toBe(0);
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

        describe('with visibility filter for Vehikl users', () => {
            beforeEach(async () => {
                wrapper = mount(Board, { propsData: { user: authVehiklUser } });
                await flushPromises();
            });

            it('combines search with visibility filter', async () => {
                const searchInput = wrapper.find('input.search-input');

                // Search for "Tamia" (sessions 4 and 5)
                await searchInput.setValue('Tamia');
                vi.advanceTimersByTime(300);
                await flushPromises();

                let visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
                expect(visibleSessions.length).toBe(2); // Both sessions by Tamia

                // Apply private filter (only session 5 is private)
                const radioButton = wrapper.find('#visibility-filters input[type=radio][id=private]');
                await radioButton.setChecked();
                await flushPromises();

                visibleSessions = wrapper.findAllComponents(GrowthSessionCard);
                expect(visibleSessions.length).toBe(1); // Only private session by Tamia
                expect(visibleSessions[0].text()).toContain('This is a private GS');
            });
        });
    });
});
