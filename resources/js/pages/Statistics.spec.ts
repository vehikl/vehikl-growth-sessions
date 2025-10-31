import { describe } from "vitest";
import { mount } from "@vue/test-utils";
import Statistics from "./Statistics.vue";
import MockAdapter from "axios-mock-adapter";
import axios from "axios";
import flushPromises from "flush-promises";
import exampleStatisticsResponse from "../../../tests/fixtures/Statistics.json";
import { HTMLInputElement } from "happy-dom";
import { DateTime } from "@/classes/DateTime";

describe("Statistics", () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
        location.search = "full-display";

        vi.spyOn(localStorage, 'getItem');
        vi.spyOn(localStorage, 'setItem');
        vi.spyOn(localStorage, 'removeItem');
    });

    afterEach(() => {
        mockBackend.restore();
        vi.restoreAllMocks();
    });

    test("renders statistics provided by the backend", async () => {
        mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

        const wrapper = mount(Statistics);
        await flushPromises();

        expect(mockBackend.history.get.length).toEqual(1);
        expect(mockBackend.history.get[0].url).toContain("/statistics");
        expect(wrapper.text()).toContain(
            exampleStatisticsResponse.users[0].name
        );
        mockBackend.restore();
    });

    test("re-fetches information with the updated start date when the user interacts with the calendar", async () => {
        mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

        const wrapper = mount(Statistics);
        await flushPromises();

        await wrapper.find("input[type=date]").setValue("2020-01-01");
        await flushPromises();

        expect(mockBackend.history.get.length).toEqual(2);
        expect(mockBackend.history.get[0].url).toContain("/statistics");
        expect(mockBackend.history.get[1].url).toContain(
            "/statistics?start_date=2020-01-01"
        );
        mockBackend.restore();
    });

    test("It allows filtering by name", async () => {
        const payload = {
            start_date: exampleStatisticsResponse.start_date,
            end_date: exampleStatisticsResponse.end_date,
            users: exampleStatisticsResponse.users.map((user) => {
                return {
                    ...user,
                    name: "John Doe",
                };
            }),
        };
        payload.users[0].name = "Jane Doe";

        mockBackend.onGet(/statistics.*/).reply(200, payload);

        const wrapper = mount(Statistics);
        await flushPromises();

        await wrapper
            .find("input[type=text][name=filter-by-name]")
            .setValue("Jane");
        await flushPromises();

        expect(wrapper.text()).toContain("Jane Doe");
        expect(wrapper.text()).not.toContain("John Doe");
    });

    describe("Filter list functionality", () => {
        test("allows adding names to the filter list", async () => {
            mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper.find("input[type=text][name=filter-by-name]").setValue("Geoffrey");
            await wrapper.findAll("button").filter(btn => btn.text() === "Apply").at(0).trigger("click");
            await flushPromises();

            expect(wrapper.text()).toContain("Geoffrey");

            expect(wrapper.find("input[type=text][name=filter-by-name]").element.value).toBe("");
        });

        test("allows removing names from the filter list", async () => {
            mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper.find("input[type=text][name=filter-by-name]").setValue("Geoffrey");
            await wrapper.findAll("button").filter(btn => btn.text() === "Apply").at(0).trigger("click");
            await flushPromises();

            expect(wrapper.text()).toContain("Geoffrey");

            await wrapper.find("button.text-red-500").trigger("click");
            await flushPromises();

            expect(wrapper.find("li").exists()).toBe(true);
            expect(wrapper.find("li").text()).toContain("-- Empty --");
        });

        test("allows clearing the entire filter list", async () => {
            mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper.find("input[type=text][name=filter-by-name]").setValue("Geoffrey");
            await wrapper.findAll("button").filter(btn => btn.text() === "Apply").at(0).trigger("click");
            await wrapper.find("input[type=text][name=filter-by-name]").setValue("Isaiah");
            await wrapper.findAll("button").filter(btn => btn.text() === "Apply").at(0).trigger("click");
            await flushPromises();

            expect(wrapper.text()).toContain("Geoffrey");
            expect(wrapper.text()).toContain("Isaiah");

            await wrapper.findAll("button").filter(btn => btn.text() === "Clear").at(0).trigger("click");
            await flushPromises();

            expect(wrapper.find("section[aria-description=Filter by list]").text()).toContain("-- Empty --");
        });

        test("filters the table based on the filter list when checkbox is checked", async () => {
            const payload = {
                start_date: exampleStatisticsResponse.start_date,
                end_date: exampleStatisticsResponse.end_date,
                users: [
                    { ...exampleStatisticsResponse.users[0], name: "Geoffrey Simonis" },
                    { ...exampleStatisticsResponse.users[1], name: "Isaiah Kutch" },
                    { ...exampleStatisticsResponse.users[2], name: "Carroll King" }
                ]
            };

            mockBackend.onGet(/statistics.*/).reply(200, payload);

            const wrapper = mount(Statistics);
            await flushPromises();

            expect(wrapper.text()).toContain("Geoffrey Simonis");
            expect(wrapper.text()).toContain("Isaiah Kutch");
            expect(wrapper.text()).toContain("Carroll King");

            await wrapper.find("input[type=text][name=filter-by-name]").setValue("Geoffrey");
            await wrapper.findAll("button").filter(btn => btn.text() === "Apply").at(0).trigger("click");
            await flushPromises();

            await wrapper.find("input[type=checkbox]").setValue(true);
            await flushPromises();

            expect(wrapper.text()).toContain("Geoffrey Simonis");
            expect(wrapper.text()).not.toContain("Isaiah Kutch");
            expect(wrapper.text()).not.toContain("Carroll King");
        });

        test("saves filter settings to localStorage", async () => {
            mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper.find("input[type=text][name=filter-by-name]").setValue("Geoffrey");
            await wrapper.findAll("button").filter(btn => btn.text() === "Apply").at(0).trigger("click");
            await flushPromises();

            await wrapper.find("input[type=checkbox]").setValue(true);
            await flushPromises();

            const settingsSaved = JSON.parse(localStorage.getItem('statistics_filter'));
            expect(settingsSaved).toEqual(expect.objectContaining({list: ['Geoffrey']}));
            expect(settingsSaved).toEqual(expect.objectContaining({shouldUseList: true}));
        });

        test("loads filter settings from localStorage on component mount", async () => {
            const filterSettings = {
                list: ["Geoffrey", "Isaiah"],
                shouldUseList: true
            };
            localStorage.setItem("statistics_filter", JSON.stringify(filterSettings));

            mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

            const wrapper = mount(Statistics);
            await flushPromises();

            expect(wrapper.text()).toContain("Geoffrey");
            expect(wrapper.text()).toContain("Isaiah");

            expect(wrapper.find("input[type=checkbox]").element.checked).toBe(true);
        });
    });

    describe("Date range buttons", () => {
        beforeEach(() => {
            DateTime.setTestNow("2025-03-13");
        });

        afterEach(() => {
            DateTime.setTestNow(new Date().toISOString());
        });

        test("'This Week' button sets date range from this week's Monday to today", async () => {
            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper
                .findAll("button")
                .find((btn) => btn.text() === "This Week")
                ?.trigger("click");
            await flushPromises();

            const startDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=start-date]"
            ).element as unknown as HTMLInputElement;
            const endDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=end-date]"
            ).element as unknown as HTMLInputElement;

            expect(startDateInput.value).toBe("2025-03-10");
            expect(endDateInput.value).toBe("2025-03-13");

            expect(mockBackend.history.get.length).toBeGreaterThan(1);
        });

        test("'This Week' button sets both start and end date to today when today is Monday", async () => {
            DateTime.setTestNow("2025-03-10");

            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper
                .findAll("button")
                .find((btn) => btn.text() === "This Week")
                ?.trigger("click");
            await flushPromises();

            const startDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=start-date]"
            ).element as unknown as HTMLInputElement;
            const endDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=end-date]"
            ).element as unknown as HTMLInputElement;

            expect(startDateInput.value).toBe("2025-03-10");
            expect(endDateInput.value).toBe("2025-03-10");

            expect(mockBackend.history.get.length).toBeGreaterThan(1);
        });

        test("'This Month' button sets date range from first Monday of the month to today", async () => {
            DateTime.setTestNow("2025-03-13");

            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper
                .findAll("button")
                .find((btn) => btn.text() === "This Month")
                ?.trigger("click");
            await flushPromises();

            const startDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=start-date]"
            ).element as unknown as HTMLInputElement;
            const endDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=end-date]"
            ).element as unknown as HTMLInputElement;

            expect(startDateInput.value).toBe("2025-03-03");
            expect(endDateInput.value).toBe("2025-03-13");

            expect(mockBackend.history.get.length).toBeGreaterThan(1);
        });

        test("'All Time' button sets date range from May 21, 2020 to today", async () => {
            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper
                .find("input[type=date][name=start-date]")
                .setValue("2022-01-01");
            await flushPromises();

            mockBackend.resetHistory();

            await wrapper
                .findAll("button")
                .find((btn) => btn.text() === "All Time")
                ?.trigger("click");
            await flushPromises();

            const startDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=start-date]"
            ).element as unknown as HTMLInputElement;
            const endDateInput: HTMLInputElement = wrapper.find(
                "input[type=date][name=end-date]"
            ).element as unknown as HTMLInputElement;

            expect(startDateInput.value).toBe("2020-05-21");
            expect(endDateInput.value).toBe("2025-03-13");

            expect(mockBackend.history.get.length).toBeGreaterThan(0);
            expect(mockBackend.history.get[0].url).toContain(
                "start_date=2020-05-21"
            );
        });
    });
});
