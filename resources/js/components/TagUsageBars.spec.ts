import TagUsageBars from '@/components/TagUsageBars.vue';
import type { ITagUsage } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

function mountBars(tags: ITagUsage[]) {
    return mount(TagUsageBars, {
        props: { tags },
        slots: { empty: '<p class="empty">Nothing tagged.</p>' },
    });
}

const vue: ITagUsage = { id: 1, name: 'Vue', sessions_count: 8 };
const testing: ITagUsage = { id: 2, name: 'Testing', sessions_count: 2 };

describe('TagUsageBars', () => {
    it('renders one row per tag, with its name and count', () => {
        const rows = mountBars([vue, testing]).findAll('[data-testid="tag-usage"]');

        expect(rows).toHaveLength(2);
        expect(rows[0].text()).toContain('Vue');
        expect(rows[0].text()).toContain('8');
        expect(rows[1].text()).toContain('Testing');
        expect(rows[1].text()).toContain('2');
    });

    it('fills the busiest tag’s bar and scales the rest against it', () => {
        const rows = mountBars([vue, testing]).findAll('[data-testid="tag-usage"]');

        expect(rows[0].find('.gs-accent-bg').attributes('style')).toContain('width: 100%');
        expect(rows[1].find('.gs-accent-bg').attributes('style')).toContain('width: 25%');
    });

    it('scales against the largest count even when it is not listed first', () => {
        // The scale must come from the whole list, so an unsorted payload cannot overflow a bar.
        const rows = mountBars([testing, vue]).findAll('[data-testid="tag-usage"]');

        expect(rows[0].find('.gs-accent-bg').attributes('style')).toContain('width: 25%');
        expect(rows[1].find('.gs-accent-bg').attributes('style')).toContain('width: 100%');
    });

    it('renders the empty slot instead of any bars when nothing has been tagged', () => {
        const wrapper = mountBars([]);

        expect(wrapper.findAll('[data-testid="tag-usage"]')).toHaveLength(0);
        expect(wrapper.find('.empty').text()).toBe('Nothing tagged.');
    });
});
