import PageContainer from '@/components/PageContainer.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('PageContainer', () => {
    it('renders slotted content inside the page shell', () => {
        const wrapper = mount(PageContainer, {
            slots: { default: '<p class="child">Hello</p>' },
        });

        expect(wrapper.find('main.gs-page').exists()).toBe(true);
        expect(wrapper.find('.child').text()).toBe('Hello');
    });

    it('constrains content to the max-width container', () => {
        const wrapper = mount(PageContainer, { slots: { default: '<span>x</span>' } });

        expect(wrapper.find('main > div').classes()).toContain('max-w-6xl');
    });
});
