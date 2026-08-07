import Statistics from '@/pages/Statistics.vue';
import { mountWithInertia } from '@/test-utils/inertia-test-helper';
import { IStatisticsDashboard } from '@/types';
import { describe, expect, test } from 'vitest';

const dashboard: IStatisticsDashboard = {
    summary: {
        lifetime_sessions_count: 240,
        sessions_this_week_count: 19,
        weekly_unique_participants_count: 18,
        average_attendance_count: 3.4,
    },
    top_hosts: [
        { id: 3, name: 'Alex Barry', avatar: 'https://example.test/alex.png', sessions_hosted_count: 4 },
        { id: 4, name: 'Sam Reed', avatar: null, sessions_hosted_count: 1 },
    ],
    tags: [
        { id: 1, name: 'Client Work', sessions_count: 4 },
        { id: 2, name: 'Social', sessions_count: 3 },
    ],
};

function mountStatistics(props: Partial<IStatisticsDashboard> = {}) {
    return mountWithInertia(Statistics, { props: { ...dashboard, ...props } });
}

describe('Statistics', () => {
    test('renders the dashboard statistics', () => {
        const wrapper = mountStatistics();

        expect(wrapper.text()).toContain('240');
        expect(wrapper.text()).toContain('Lifetime sessions');
        expect(wrapper.text()).toContain('Sessions this week');
        expect(wrapper.text()).toContain('Alex Barry');
        expect(wrapper.text()).toContain('Client Work');
    });

    test('renders top hosts in the order given by the server', () => {
        const wrapper = mountStatistics();

        const hosts = wrapper.findAll('section');
        expect(wrapper.text()).toContain('4 sessions');
        expect(wrapper.text()).toContain('1 session');
        expect(hosts.length).toBeGreaterThan(0);
    });

    test('shows each top host’s photo, falling back to their initials', () => {
        const avatars = mountStatistics().findAll('[data-testid="top-host-avatar"]');

        expect(avatars[0].find('img').attributes('src')).toBe('https://example.test/alex.png');
        expect(avatars[1].find('img').exists()).toBe(false);
        expect(avatars[1].text()).toBe('SR');
    });

    test('shows an empty state when no sessions were hosted this week', () => {
        const wrapper = mountStatistics({ top_hosts: [] });

        expect(wrapper.text()).toContain('No hosted sessions this week.');
    });
});
