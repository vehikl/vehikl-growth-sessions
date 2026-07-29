import PageHeader from '@/components/PageHeader.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('PageHeader', () => {
    it('renders the title in the heading', () => {
        const wrapper = mount(PageHeader, { props: { title: 'Growth Sessions' } });

        expect(wrapper.find('h1').text()).toBe('Growth Sessions');
    });

    it('omits the eyebrow row when neither eyebrow nor badge is provided', () => {
        const wrapper = mount(PageHeader, { props: { title: 'Growth Sessions' } });

        expect(wrapper.find('span.gs-accent-text').exists()).toBe(false);
    });

    it('renders the eyebrow and badge when provided', () => {
        const wrapper = mount(PageHeader, {
            props: { title: 'Growth Sessions', eyebrow: 'About', badge: 'New' },
        });

        expect(wrapper.text()).toContain('About');
        expect(wrapper.text()).toContain('New');
    });

    it('renders the lead from the prop', () => {
        const wrapper = mount(PageHeader, {
            props: { title: 'Growth Sessions', lead: 'Learn together, grow together.' },
        });

        expect(wrapper.find('p').text()).toContain('Learn together, grow together.');
    });

    it('renders no lead paragraph when neither the prop nor the slot is provided', () => {
        const wrapper = mount(PageHeader, { props: { title: 'Growth Sessions' } });

        expect(wrapper.find('p').exists()).toBe(false);
    });

    it('lets a lead slot override the prop', () => {
        const wrapper = mount(PageHeader, {
            props: { title: 'Growth Sessions', lead: 'from prop' },
            slots: { lead: '<em class="slot-lead">from slot</em>' },
        });

        expect(wrapper.find('.slot-lead').text()).toBe('from slot');
    });
});
