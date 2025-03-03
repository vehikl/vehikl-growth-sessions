import { describe } from "vitest";
import { mount } from "@vue/test-utils";
import Statistics from "./Statistics.vue";
import MockAdapter from "axios-mock-adapter";
import axios from "axios";
import flushPromises from "flush-promises";
import exampleStatisticsResponse from "../../../tests/fixtures/Statistics.json";
import {HTMLInputElement} from "happy-dom";

describe("Statistics", () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
        location.search = 'full-display'
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

    describe("Last Monday button", () => {
        test("sets start date to the same date when end date is a Monday", async () => {
            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            await wrapper
                .find("input[type=date][name=end-date]")
                .setValue("2024-03-18");
            await wrapper.find("button.last-monday-btn").trigger("click");
            await flushPromises();

            const element: HTMLInputElement = wrapper.find("input[type=date][name=start-date]").element as unknown as HTMLInputElement;
            expect(element.value).toBe("2024-03-18");
        });

        test("sets start date to the closest previous Monday when end date is not a Monday", async () => {
            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            // Set end date to a Wednesday (2024-03-20)
            await wrapper
                .find("input[type=date][name=end-date]")
                .setValue("2024-03-20");
            await wrapper.find("button.last-monday-btn").trigger("click");
            await flushPromises();

            const element: HTMLInputElement = wrapper.find("input[type=date][name=start-date]").element as unknown as HTMLInputElement;
            expect(element.value).toBe("2024-03-18");
        });

        test("updates the statistics when Last Monday button is clicked", async () => {
            mockBackend
                .onGet(/statistics.*/)
                .reply(200, exampleStatisticsResponse);
            const wrapper = mount(Statistics);
            await flushPromises();

            // Set end date to a Wednesday (2024-03-20)
            await wrapper
                .find("input[type=date][name=end-date]")
                .setValue("2024-03-20");
            await wrapper.find("button.last-monday-btn").trigger("click");
            await flushPromises();

            expect(mockBackend.history.get.length).toBeGreaterThan(1);
            expect(
                mockBackend.history.get[mockBackend.history.get.length - 1].url
            ).toContain("start_date=2024-03-18");
        });
    });
});
