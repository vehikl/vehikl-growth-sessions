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
})