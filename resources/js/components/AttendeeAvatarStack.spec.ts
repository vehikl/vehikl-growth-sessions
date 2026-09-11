import AttendeeAvatarStack from '@/components/AttendeeAvatarStack.vue';
import { IMemberSummary } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

function members(count: number): IMemberSummary[] {
    return Array.from({ length: count }, (_, i) => ({ id: i + 1, name: `Member ${i + 1}`, avatar: `https://example.test/${i + 1}.png` }));
}

describe('AttendeeAvatarStack', () => {
    it('shows one face per attendee', () => {
        const wrapper = mount(AttendeeAvatarStack, { props: { members: members(3) } });

        expect(wrapper.findAll('[data-testid="attendee-avatar"]')).toHaveLength(3);
        expect(wrapper.find('[data-testid="attendee-overflow"]').exists()).toBe(false);
    });

    it('renders nothing when nobody has joined', () => {
        const wrapper = mount(AttendeeAvatarStack, { props: { members: [] } });

        expect(wrapper.find('[data-testid="attendee-avatar"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="attendee-overflow"]').exists()).toBe(false);
    });

    it('collapses everyone past the limit into a +N bubble', () => {
        const wrapper = mount(AttendeeAvatarStack, { props: { members: members(8), max: 5 } });

        expect(wrapper.findAll('[data-testid="attendee-avatar"]')).toHaveLength(5);
        expect(wrapper.find('[data-testid="attendee-overflow"]').text()).toBe('+3');
    });

    it('does not show a bubble when the attendees fit exactly', () => {
        const wrapper = mount(AttendeeAvatarStack, { props: { members: members(5), max: 5 } });

        expect(wrapper.findAll('[data-testid="attendee-avatar"]')).toHaveLength(5);
        expect(wrapper.find('[data-testid="attendee-overflow"]').exists()).toBe(false);
    });

    it('names every attendee, hidden or not, for hover and assistive tech', () => {
        const wrapper = mount(AttendeeAvatarStack, { props: { members: members(7), max: 5 } });

        expect(wrapper.attributes('title')).toBe('Member 1, Member 2, Member 3, Member 4, Member 5, Member 6, Member 7');
        expect(wrapper.attributes('aria-label')).toBe('Attending: Member 1, Member 2, Member 3, Member 4, Member 5, Member 6, Member 7');
    });

    it('draws each face from the member’s avatar and name', () => {
        const wrapper = mount(AttendeeAvatarStack, { props: { members: [{ id: 1, name: 'Alex Barry', avatar: null }] } });

        expect(wrapper.find('[data-testid="attendee-avatar"]').text()).toBe('AB');
    });
});
