import { DateTime } from '@/classes/DateTime';
import Statistics from '@/pages/Statistics.vue';
import { mountWithInertia } from '@/test-utils/inertia-test-helper';
import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import flushPromises from 'flush-promises';
import { afterEach, beforeEach, describe, expect, test } from 'vitest';

const response = {
    start_date: '2025-03-10',
    end_date: '2025-03-13',
    summary: {
        lifetime_sessions_count: 240,
        sessions_this_week_count: 19,
        weekly_unique_participants_count: 18,
        average_attendance_count: 3.4,
    },
    tags: [
        { id: 1, name: 'Client Work', sessions_count: 4 },
        { id: 2, name: 'Social', sessions_count: 3 },
    ],
    current_user: {
        name: 'Current User',
        user_id: 1,
        has_mobbed_with: [],
        has_mobbed_with_count: 0,
        has_not_mobbed_with: [{ name: 'Brady Deroy', id: 2 }],
        has_not_mobbed_with_count: 1,
        total_sessions_count: 2,
        sessions_hosted_count: 1,
        sessions_attended_count: 1,
        sessions_watched_count: 0,
    },
    users: [
        {
            name: 'Alex Barry',
            user_id: 3,
            has_mobbed_with: [],
            has_mobbed_with_count: 0,
            has_not_mobbed_with: [],
            has_not_mobbed_with_count: 0,
            total_sessions_count: 4,
            sessions_hosted_count: 4,
            sessions_attended_count: 0,
            sessions_watched_count: 0,
        },
    ],
};

describe('Statistics', () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        DateTime.setTestNow('2025-03-13');
        mockBackend = new MockAdapter(axios);
        mockBackend.onGet(/statistics.*/).reply(200, response);
    });

    afterEach(() => {
        mockBackend.restore();
        DateTime.setTestNow(new Date().toISOString());
    });

    test('renders the dashboard statistics', async () => {
        const wrapper = mountWithInertia(Statistics);
        await flushPromises();

        expect(wrapper.text()).toContain('240');
        expect(wrapper.text()).toContain('Lifetime sessions');
        expect(wrapper.text()).toContain('Sessions this week');
        expect(wrapper.text()).toContain('Alex Barry');
        expect(wrapper.text()).toContain('Client Work');
    });

    test('renders the current user’s yet-to-mob-with members', async () => {
        const wrapper = mountWithInertia(Statistics);
        await flushPromises();

        expect(wrapper.text()).toContain('Yet to mob with');
        expect(wrapper.text()).toContain('Brady Deroy');
    });

    test('requests statistics for the current week', async () => {
        mountWithInertia(Statistics);
        await flushPromises();

        expect(mockBackend.history.get[0].url).toContain('start_date=2025-03-10');
        expect(mockBackend.history.get[0].url).toContain('end_date=2025-03-13');
    });
});
