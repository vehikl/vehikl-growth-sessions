import SeriesField from '@/components/legacy/SeriesField.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const options = ['Friday Pairing', 'Vue Deep Dive', 'Vue Router'];

function mountField(modelValue = '') {
    return mount(SeriesField, { props: { id: 'series-name', options, modelValue } });
}

function suggestionsOf(wrapper: ReturnType<typeof mountField>) {
    return wrapper.findAll('[role="option"]').map((option) => option.text());
}

describe('SeriesField', () => {
    it('keeps the list down until the field is reached for', () => {
        expect(mountField().find('[role="listbox"]').exists()).toBe(false);
    });

    it('offers every series already running to an empty field', async () => {
        const wrapper = mountField();
        await wrapper.find('input').trigger('focus');

        expect(suggestionsOf(wrapper)).toEqual(options);
    });

    it('narrows the suggestions to what has been typed, wherever it falls in the name', async () => {
        const wrapper = mountField('vue');
        await wrapper.find('input').trigger('focus');

        expect(suggestionsOf(wrapper)).toEqual(['Vue Deep Dive', 'Vue Router']);
    });

    it('reports what was typed as it is typed', async () => {
        const wrapper = mountField();
        await wrapper.find('input').setValue('Cocktails');

        expect(wrapper.emitted('update:modelValue')).toEqual([['Cocktails']]);
    });

    it('takes the whole name when a suggestion is clicked', async () => {
        const wrapper = mountField('vue d');
        await wrapper.find('input').trigger('focus');
        await wrapper.findAll('[role="option"] button')[0].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([['Vue Deep Dive']]);
        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
    });

    // Nothing left to choose: the field already holds the only match.
    it('stops suggesting a name once the field holds exactly that name', async () => {
        const wrapper = mount(SeriesField, { props: { id: 'series-name', options: ['Vue Deep Dive'], modelValue: 'vue deep dive' } });
        await wrapper.find('input').trigger('focus');

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
    });

    it('says nothing when what was typed matches no series at all', async () => {
        const wrapper = mountField('Cocktails');
        await wrapper.find('input').trigger('focus');

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
    });

    describe('from the keyboard', () => {
        it('walks the suggestions and takes the highlighted one', async () => {
            const wrapper = mountField('vue');
            await wrapper.find('input').trigger('focus');
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toEqual([['Vue Router']]);
        });

        it('hands the field back when it walks off the end of the list', async () => {
            const wrapper = mountField('vue');
            await wrapper.find('input').trigger('focus');
            // Two suggestions, so the third press lands past the last one.
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });
            await wrapper.find('input').trigger('keydown', { key: 'ArrowDown' });

            expect(wrapper.findAll('[aria-selected="true"]')).toHaveLength(0);

            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        });

        it('walks upwards from the field into the last suggestion', async () => {
            const wrapper = mountField('vue');
            await wrapper.find('input').trigger('focus');
            await wrapper.find('input').trigger('keydown', { key: 'ArrowUp' });
            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toEqual([['Vue Router']]);
        });

        // Enter must still submit the form when nothing is highlighted.
        it('leaves what was typed alone when nothing is highlighted', async () => {
            const wrapper = mountField('vue');
            await wrapper.find('input').trigger('focus');
            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            expect(wrapper.emitted('update:modelValue')).toBeUndefined();
            expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
        });

        it('dismisses the list on escape without changing what was typed', async () => {
            const wrapper = mountField('vue');
            await wrapper.find('input').trigger('focus');
            await wrapper.find('input').trigger('keydown', { key: 'Escape' });

            expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
            expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        });
    });

    it('marks the field when the server rejected the name', () => {
        const wrapper = mount(SeriesField, { props: { id: 'series-name', options, modelValue: '', invalid: true } });

        expect(wrapper.find('input').classes()).toContain('error-outline');
    });
});
