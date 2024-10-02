import {describe} from "vitest";
import {mount} from "@vue/test-utils";
import Statistics from "./Statistics.vue";
import MockAdapter from "axios-mock-adapter";
import axios from "axios";
import flushPromises from "flush-promises";
import exampleStatisticsResponse from "../../../tests/fixtures/Statistics.json"

describe('Statistics', () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
    })

    afterEach(() => {
        mockBackend.restore();
    });

    test('renders statistics provided by the backend', async () => {
        mockBackend.onGet('/statistics').reply(200, exampleStatisticsResponse);

        const wrapper = mount(Statistics);
        await flushPromises();

        expect(mockBackend.history.get.length).toEqual(1);
        expect(mockBackend.history.get[0].url).toEqual('/statistics');
        expect(wrapper.text()).toContain(exampleStatisticsResponse.users[0].name);
        mockBackend.restore();
    })

    test('re-fetches information with the updated start date when the user interacts with the calendar', async () => {
        mockBackend.onGet(/statistics.*/).reply(200, exampleStatisticsResponse);

        const wrapper = mount(Statistics);
        await flushPromises();

        await wrapper.find("input[type=date]").setValue("2020-01-01");
        await flushPromises();

        expect(mockBackend.history.get.length).toEqual(2);
        expect(mockBackend.history.get[0].url).toEqual('/statistics');
        expect(mockBackend.history.get[1].url).toEqual('/statistics?start_date=2020-01-01');
        mockBackend.restore();
    })

    test('It allows filtering by name', async () => {
        const payload = {
            start_date: exampleStatisticsResponse.start_date,
            end_date: exampleStatisticsResponse.end_date,
            users: exampleStatisticsResponse.users.map(user => {
                return {
                    ...user,
                    name: "John Doe"
                }
            })
        }
        payload.users[0].name = "Jane Doe";

        mockBackend.onGet(/statistics.*/).reply(200, payload);

        const wrapper = mount(Statistics);
        await flushPromises();

        await wrapper.find("input[type=text][name=filter-by-name]").setValue("Jane");
        await flushPromises();

        expect(wrapper.text()).toContain("Jane Doe");
        expect(wrapper.text()).not.toContain("John Doe");
    })
})