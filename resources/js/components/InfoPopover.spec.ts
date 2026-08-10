import InfoPopover from '@/components/InfoPopover.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

function mountPopover() {
    return mount(InfoPopover, {
        props: { label: 'About Mob Squad' },
        slots: { default: 'The five people you have mobbed with most.' },
        attachTo: document.body,
    });
}

describe('InfoPopover', () => {
    it('stays closed until the trigger is pressed', () => {
        const wrapper = mountPopover();

        expect(wrapper.find('[data-testid="info-popover-panel"]').exists()).toBe(false);
    });

    it('reveals the explanation when the trigger is pressed', async () => {
        const wrapper = mountPopover();

        await wrapper.find('[data-testid="info-popover-trigger"]').trigger('click');

        expect(wrapper.find('[data-testid="info-popover-panel"]').text()).toContain('The five people you have mobbed with most.');
    });

    it('hides the explanation again when the trigger is pressed a second time', async () => {
        const wrapper = mountPopover();
        const trigger = wrapper.find('[data-testid="info-popover-trigger"]');

        await trigger.trigger('click');
        await trigger.trigger('click');

        expect(wrapper.find('[data-testid="info-popover-panel"]').exists()).toBe(false);
    });

    it('tells assistive tech what the trigger opens, and whether it is open', async () => {
        const wrapper = mountPopover();
        const trigger = wrapper.find('[data-testid="info-popover-trigger"]');

        expect(trigger.attributes('aria-label')).toBe('About Mob Squad');
        expect(trigger.attributes('aria-expanded')).toBe('false');

        await trigger.trigger('click');

        expect(trigger.attributes('aria-expanded')).toBe('true');
    });

    it('names the panel after its trigger, so the explanation is not orphaned', async () => {
        const wrapper = mountPopover();
        const trigger = wrapper.find('[data-testid="info-popover-trigger"]');

        await trigger.trigger('click');

        expect(trigger.attributes('aria-controls')).toBe(wrapper.find('[data-testid="info-popover-panel"]').attributes('id'));
    });

    it('gives each popover on a page its own id, so triggers do not point at one another’s panel', () => {
        const page = mount(
            {
                components: { InfoPopover },
                template: `
                    <div>
                        <InfoPopover label="About Mob Squad">The five people you have mobbed with most.</InfoPopover>
                        <InfoPopover label="About Top tags">The tags you have used most.</InfoPopover>
                    </div>
                `,
            },
            { attachTo: document.body },
        );

        const [first, second] = page.findAll('[data-testid="info-popover-trigger"]');

        expect(first.attributes('aria-controls')).not.toBe(second.attributes('aria-controls'));
    });

    it('dismisses on Escape, so the keyboard is never trapped by an aside', async () => {
        const wrapper = mountPopover();

        await wrapper.find('[data-testid="info-popover-trigger"]').trigger('click');
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="info-popover-panel"]').exists()).toBe(false);
    });

    it('dismisses when a click lands outside it', async () => {
        const wrapper = mountPopover();

        await wrapper.find('[data-testid="info-popover-trigger"]').trigger('click');
        document.body.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="info-popover-panel"]').exists()).toBe(false);
    });

    it('stops listening for Escape once it is gone from the page', async () => {
        const wrapper = mountPopover();
        await wrapper.find('[data-testid="info-popover-trigger"]').trigger('click');

        wrapper.unmount();

        expect(() => window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))).not.toThrow();
    });
});
