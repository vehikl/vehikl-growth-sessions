import { mount } from '@vue/test-utils';
import { vi } from 'vitest';
import ConfirmationModal from './ConfirmationModal.vue';

describe('ConfirmationModal', () => {
    const defaultProps = {
        state: 'closed' as const,
        title: 'Test Title',
        message: 'Test message content',
    };

    beforeEach(() => {
        HTMLDialogElement.prototype.showModal = vi.fn();
        HTMLDialogElement.prototype.close = vi.fn();
    });

    it('renders with provided title and message', () => {
        const wrapper = mount(ConfirmationModal, {
            props: { ...defaultProps, state: 'open' },
        });

        expect(wrapper.text()).toContain('Test Title');
        expect(wrapper.text()).toContain('Test message content');
    });

    it('renders custom confirm label', () => {
        const wrapper = mount(ConfirmationModal, {
            props: {
                ...defaultProps,
                state: 'open',
                confirmLabel: 'Yes, Proceed',
            },
        });

        expect(wrapper.text()).toContain('Yes, Proceed');
    });

    it("emits 'confirmed' when confirm button is clicked", async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { ...defaultProps, state: 'open' },
        });

        await wrapper.find('.confirm-button').trigger('click');

        expect(wrapper.emitted('confirmed')).toHaveLength(1);
    });

    it("emits 'dismissed' when cancel button is clicked", async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { ...defaultProps, state: 'open' },
        });

        const buttons = wrapper.findAll('button');
        const cancelButton = buttons.find((btn) => btn.text() === 'Cancel');

        await cancelButton?.trigger('click');

        expect(wrapper.emitted('dismissed')).toHaveLength(1);
    });

    it("emits 'dismissed' when dialog is closed while state is open", async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { ...defaultProps, state: 'open' },
        });

        const dialog = wrapper.find('dialog');
        await dialog.trigger('close');

        expect(wrapper.emitted('dismissed')).toHaveLength(1);
    });
});
