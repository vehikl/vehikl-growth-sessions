import MemberAvatar from '@/components/MemberAvatar.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('MemberAvatar', () => {
    it('shows the member’s photo when they have one', () => {
        const wrapper = mount(MemberAvatar, { props: { name: 'Alex Barry', avatar: 'https://example.test/alex.png' } });

        expect(wrapper.find('img').attributes('src')).toBe('https://example.test/alex.png');
        expect(wrapper.find('img').attributes('alt')).toBe('Alex Barry');
    });

    it('falls back to the member’s initials when they have no photo', () => {
        const wrapper = mount(MemberAvatar, { props: { name: 'Gavin Abeele', avatar: null } });

        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.text()).toBe('GA');
    });

    it('treats an empty avatar the same as an absent one', () => {
        const wrapper = mount(MemberAvatar, { props: { name: 'Gavin Abeele', avatar: '' } });

        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.text()).toBe('GA');
    });

    it('falls back to initials when no avatar is passed at all', () => {
        const wrapper = mount(MemberAvatar, { props: { name: 'Gavin Abeele' } });

        expect(wrapper.text()).toBe('GA');
    });

    it('sizes the circle to the requested size', () => {
        const small = mount(MemberAvatar, { props: { name: 'Alex Barry' } });
        const medium = mount(MemberAvatar, { props: { name: 'Alex Barry', size: 'md' } });

        expect(small.classes()).toContain('h-8');
        expect(medium.classes()).toContain('h-10');
    });

    /** The circle stays the member's own colour whether or not a photo ever loads over it. */
    it('colours the circle from the member’s name, photo or not', () => {
        const withPhoto = mount(MemberAvatar, { props: { name: 'Alex Barry', avatar: 'https://example.test/alex.png' } });
        const withoutPhoto = mount(MemberAvatar, { props: { name: 'Alex Barry' } });
        const otherMember = mount(MemberAvatar, { props: { name: 'Gavin Abeele' } });

        expect(withPhoto.attributes('style')).toContain('background-color');
        expect(withPhoto.attributes('style')).toBe(withoutPhoto.attributes('style'));
        expect(otherMember.attributes('style')).not.toBe(withoutPhoto.attributes('style'));
    });
});
