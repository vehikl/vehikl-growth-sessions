import { describe } from "vitest";
import { mount } from "@vue/test-utils";
import Statistics from "./Statistics.vue";
import MockAdapter from "axios-mock-adapter";
import axios from "axios";
import flushPromises from "flush-promises";
import exampleStatisticsResponse from "../../../tests/fixtures/Statistics.json";
import { HTMLInputElement } from "happy-dom";
import { DateTime } from "../classes/DateTime";

describe("Statistics", () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
        location.search = "full-display";
    });

    afterEach(() => {
        mockBackend.restore();
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

    describe("Date range buttons", () => {
        beforeEach(() => {
            // Set a fixed date for testing (Thursday, March 13, 2025)
            DateTime.setTestNow("2025-03-13");
        });

        afterEach(() => {
            // Reset the date after each test
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

            // Should set start date to Monday of this week (March 10, 2025)
            expect(startDateInput.value).toBe("2025-03-10");
            // Should set end date to today (March 13, 2025)
            expect(endDateInput.value).toBe("2025-03-13");

            // Should update statistics
            expect(mockBackend.history.get.length).toBeGreaterThan(1);
        });

        test("'This Week' button sets both start and end date to today when today is Monday", async () => {
            // Set today to a Monday (March 10, 2025)
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

            // Both start date and end date should be set to today (March 10, 2025)
            expect(startDateInput.value).toBe("2025-03-10");
            expect(endDateInput.value).toBe("2025-03-10");

            // Should update statistics
            expect(mockBackend.history.get.length).toBeGreaterThan(1);
        });

        test("'This Month' button sets date range from first Monday of the month to today", async () => {
            // Reset to Thursday, March 13, 2025
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

            // Should set start date to first Monday of month (March 3, 2025)
            expect(startDateInput.value).toBe("2025-03-03");
            // Should set end date to today (March 13, 2025)
            expect(endDateInput.value).toBe("2025-03-13");

            // Should update statistics
            expect(mockBackend.history.get.length).toBeGreaterThan(1);
        });

        test("'All Time' button sets date range from May 21, 2020 to today", async () => {
            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            // First change the start date to something else
            await wrapper
                .find("input[type=date][name=start-date]")
                .setValue("2022-01-01");
            await flushPromises();

            // Reset the mock history to have a clean slate
            mockBackend.resetHistory();

            // Now click the All Time button
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

            // Should set start date to May 21, 2020
            expect(startDateInput.value).toBe("2020-05-21");
            // Should set end date to today (March 13, 2025)
            expect(endDateInput.value).toBe("2025-03-13");

            // Should have made an API call
            expect(mockBackend.history.get.length).toBeGreaterThan(0);
            expect(mockBackend.history.get[0].url).toContain(
                "start_date=2020-05-21"
            );
        });
    });
});
