import {describe} from "vitest";
import {mount} from "@vue/test-utils";
import Statistics from "./Statistics.vue";
import MockAdapter from "axios-mock-adapter";
import axios from "axios";
import flushPromises from "flush-promises";

const exampleStatisticsResponse = {
    "start_date": "2024-09-09",
    "end_date": "2024-09-11",
    "users": [{
        "name": "Geoffrey Simonis",
        "user_id": 3,
        "has_mobbed_with": [],
        "has_mobbed_with_count": 0,
        "has_not_mobbed_with": [{"name": "Dr. Delbert Deckow PhD", "user_id": 4}, {
            "name": "Prof. Princess Wiegand Sr.",
            "user_id": 5
        }, {"name": "Isaiah Kutch", "user_id": 6}, {"name": "Carroll King", "user_id": 7}],
        "has_not_mobbed_with_count": 4,
        "total_sessions_count": 1,
        "sessions_hosted_count": 1,
        "sessions_attended_count": 0,
        "sessions_watched_count": 0
    }, {
        "name": "Dr. Delbert Deckow PhD",
        "user_id": 4,
        "has_mobbed_with": [],
        "has_mobbed_with_count": 0,
        "has_not_mobbed_with": [{"name": "Geoffrey Simonis", "user_id": 3}, {
            "name": "Prof. Princess Wiegand Sr.",
            "user_id": 5
        }, {"name": "Isaiah Kutch", "user_id": 6}, {"name": "Carroll King", "user_id": 7}],
        "has_not_mobbed_with_count": 4,
        "total_sessions_count": 1,
        "sessions_hosted_count": 1,
        "sessions_attended_count": 0,
        "sessions_watched_count": 0
    }, {
        "name": "Prof. Princess Wiegand Sr.",
        "user_id": 5,
        "has_mobbed_with": [],
        "has_mobbed_with_count": 0,
        "has_not_mobbed_with": [{"name": "Geoffrey Simonis", "user_id": 3}, {
            "name": "Dr. Delbert Deckow PhD",
            "user_id": 4
        }, {"name": "Isaiah Kutch", "user_id": 6}, {"name": "Carroll King", "user_id": 7}],
        "has_not_mobbed_with_count": 4,
        "total_sessions_count": 1,
        "sessions_hosted_count": 1,
        "sessions_attended_count": 0,
        "sessions_watched_count": 0
    }, {
        "name": "Isaiah Kutch",
        "user_id": 6,
        "has_mobbed_with": [],
        "has_mobbed_with_count": 0,
        "has_not_mobbed_with": [{"name": "Geoffrey Simonis", "user_id": 3}, {
            "name": "Dr. Delbert Deckow PhD",
            "user_id": 4
        }, {"name": "Prof. Princess Wiegand Sr.", "user_id": 5}, {"name": "Carroll King", "user_id": 7}],
        "has_not_mobbed_with_count": 4,
        "total_sessions_count": 1,
        "sessions_hosted_count": 1,
        "sessions_attended_count": 0,
        "sessions_watched_count": 0
    }, {
        "name": "Carroll King",
        "user_id": 7,
        "has_mobbed_with": [],
        "has_mobbed_with_count": 0,
        "has_not_mobbed_with": [{"name": "Geoffrey Simonis", "user_id": 3}, {
            "name": "Dr. Delbert Deckow PhD",
            "user_id": 4
        }, {"name": "Prof. Princess Wiegand Sr.", "user_id": 5}, {"name": "Isaiah Kutch", "user_id": 6}],
        "has_not_mobbed_with_count": 4,
        "total_sessions_count": 1,
        "sessions_hosted_count": 1,
        "sessions_attended_count": 0,
        "sessions_watched_count": 0
    }]
};
describe('Statistics', () => {
    test('renders statistics provided by the backend', async () => {
        const mockBackend: MockAdapter = new MockAdapter(axios);
        mockBackend.onGet('/statistics').reply(200, exampleStatisticsResponse);

        const wrapper = mount(Statistics);
        await flushPromises();

        expect(mockBackend.history.get.length).toEqual(1);
        expect(mockBackend.history.get[0].url).toEqual('/statistics');
        expect(wrapper.text()).toContain(exampleStatisticsResponse.users[0].name);
        mockBackend.restore();
    })
})