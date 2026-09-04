import TagField from '@/components/legacy/TagField.vue';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';

const options = [
    { label: 'Laravel', value: '1' },
    { label: 'Vue', value: '2' },
    { label: 'Vue Router', value: '3' },
];

function mountField(modelValue: string[] = []) {
    return mount(TagField, { props: { id: 'tags', options, modelValue } });
}

function suggestionsOf(wrapper: ReturnType<typeof mountField>) {
    return wrapper.findAll('[role="option"]').map((option) => option.text());
}

function chipsOf(wrapper: ReturnType<typeof mountField>) {
    return wrapper.findAll('[data-testid^="tag-chip-"]').map((chip) => chip.text().trim());
}

describe('TagField', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('keeps the list down until the field is reached for', () => {
        expect(mountField().find('[role="listbox"]').exists()).toBe(false);
    });

    it('offers the whole vocabulary to an untouched field', async () => {
        const wrapper = mountField();
        await wrapper.find('input').trigger('focus');

        expect(suggestionsOf(wrapper)).toEqual(['Laravel', 'Vue', 'Vue Router']);
    });

    it('opens the list above the field when the dialog has more room there', async () => {
        const dialog = document.createElement('dialog');
        document.body.appendChild(dialog);

        const wrapper = mount(TagField, { attachTo: dialog, props: { id: 'tags', options, modelValue: [] } });
        dialog.getBoundingClientRect = () => ({ top: 32, bottom: 868 }) as DOMRect;
        wrapper.find('.tag-field').element.getBoundingClientRect = () => ({ top: 738, bottom: 780 }) as DOMRect;

        await wrapper.find('input').trigger('focus');

        expect(wrapper.find('[role="listbox"]').classes()).toContain('bottom-full');
    });

    it('opens the list below the field when the dialog has room there', async () => {
        const dialog = document.createElement('dialog');
        document.body.appendChild(dialog);

        const wrapper = mount(TagField, { attachTo: dialog, props: { id: 'tags', options, modelValue: [] } });
        dialog.getBoundingClientRect = () => ({ top: 32, bottom: 868 }) as DOMRect;
        wrapper.find('.tag-field').element.getBoundingClientRect = () => ({ top: 120, bottom: 162 }) as DOMRect;

        await wrapper.find('input').trigger('focus');

        expect(wrapper.find('[role="listbox"]').classes()).toContain('top-full');
    });

    it('narrows the suggestions to what has been typed, wherever it falls in the name', async () => {
        const wrapper = mountField();
        await wrapper.find('input').setValue('vue');

        expect(suggestionsOf(wrapper)).toEqual(['Vue', 'Vue Router']);
    });

    it('says nothing when what was typed matches no tag at all', async () => {
        const wrapper = mountField();
        await wrapper.find('input').setValue('Cocktails');

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
    });

    it('adds a tag when it is chosen', async () => {
        const wrapper = mountField();
        await wrapper.find('input').trigger('focus');
        await wrapper.find('[data-testid="tag-option-2"]').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[['2']]]);
    });

    it('keeps the tags already on when another is added', async () => {
        const wrapper = mountField(['1']);
        await wrapper.find('input').trigger('focus');
        await wrapper.find('[data-testid="tag-option-2"]').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[['1', '2']]]);
    });

    // Tagging is usually plural, so reopening the list between every tag would cost more than it saves.
    it('leaves the list open after a tag is chosen', async () => {
        const wrapper = mountField();
        await wrapper.find('input').trigger('focus');
        await wrapper.find('[data-testid="tag-option-2"]').trigger('click');

        expect(wrapper.find('[role="listbox"]').exists()).toBe(true);
    });

    it('spends the search once it has found what it was typed for', async () => {
        const wrapper = mountField();
        await wrapper.find('input').setValue('vue');
        await wrapper.find('[data-testid="tag-option-2"]').trigger('click');

        expect((wrapper.find('input').element as HTMLInputElement).value).toBe('');
        expect(suggestionsOf(wrapper)).toEqual(['Laravel', 'Vue', 'Vue Router']);
    });

    it('shows what is on as chips in the field', () => {
        expect(chipsOf(mountField(['1', '3']))).toEqual(['Laravel', 'Vue Router']);
    });

    it('orders the chips by the vocabulary rather than by when each was picked', () => {
        expect(chipsOf(mountField(['3', '1']))).toEqual(['Laravel', 'Vue Router']);
    });

    it('marks the tags that are already on', async () => {
        const wrapper = mountField(['2']);
        await wrapper.find('input').trigger('focus');

        expect(wrapper.findAll('[role="option"]').map((option) => option.attributes('aria-selected'))).toEqual(['false', 'true', 'false']);
    });

    it('takes a tag back off when it is chosen again', async () => {
        const wrapper = mountField(['1', '2']);
        await wrapper.find('input').trigger('focus');
        await wrapper.find('[data-testid="tag-option-1"]').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[['2']]]);
    });

    it('takes a tag off from its chip', async () => {
        const wrapper = mountField(['1', '2']);
        await wrapper.find('[aria-label="Remove Laravel"]').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[['2']]]);
    });

    describe('from the keyboard', () => {
        it('walks the suggestions and takes the highlighted one', async () => {
            const wrapper = mountField();
            await wrapper.find('input').setValue('vue');
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toEqual([[['3']]]);
        });

        it('hands the field back when it walks off the end of the list', async () => {
            const wrapper = mountField();
            await wrapper.find('input').setValue('vue');
            // Two suggestions, so the third press lands past the last one.
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });

            expect(wrapper.findAll('[aria-selected="true"]')).toHaveLength(0);

            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        });

        it('walks upwards from the field into the last suggestion', async () => {
            const wrapper = mountField();
            await wrapper.find('input').setValue('vue');
            await wrapper.find('input').trigger('keydown', { key: 'ArrowUp' });
            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toEqual([[['3']]]);
        });

        // Enter must still submit the form when the visitor was done tagging.
        it('tags nothing when nothing is highlighted', async () => {
            const wrapper = mountField();
            await wrapper.find('input').trigger('focus');
            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toBeUndefined();
            expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
        });

        it('takes the last tag back off on backspace in an empty field', async () => {
            const wrapper = mountField(['1', '3']);
            await wrapper.find('input').trigger('keydown', { key: 'Backspace' });

            expect(wrapper.emitted('update:modelValue')).toEqual([[['1']]]);
        });

        it('leaves the tags alone while there is still search text to delete', async () => {
            const wrapper = mountField(['1']);
            await wrapper.find('input').setValue('vu');
            await wrapper.find('input').trigger('keydown', { key: 'Backspace' });

            expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        });

        it('dismisses the list on escape without changing the tags', async () => {
            const wrapper = mountField();
            await wrapper.find('input').setValue('vue');
            await wrapper.find('input').trigger('keydown', { key: 'Escape' });

            expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
            expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        });
    });

    it('marks the field when the server rejected the tags', () => {
        const wrapper = mount(TagField, { props: { id: 'tags', options, modelValue: [], invalid: true } });

        expect(wrapper.find('.tag-field > div').classes()).toContain('error-outline');
    });
});
