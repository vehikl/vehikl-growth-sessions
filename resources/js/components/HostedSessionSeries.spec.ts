import HostedSessionSeries from '@/components/HostedSessionSeries.vue';
import { SeriesApi } from '@/services/SeriesApi';
import { mount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { beforeEach, describe, expect, it, vi } from 'vitest';

function mountFiler(seriesName: string | null = null) {
    return mount(HostedSessionSeries, {
        props: { sessionId: 7, sessionTitle: 'Vue Testing Deep Dive', seriesName },
    });
}

describe('HostedSessionSeries', () => {
    beforeEach(() => {
        SeriesApi.index = vi.fn().mockResolvedValue(['Friday Pairing', 'Vue Deep Dive']);
        SeriesApi.file = vi.fn().mockImplementation((_id: number, name: string | null) => Promise.resolve(name));
    });

    it('invites a session that stands on its own to join one', () => {
        expect(mountFiler().text()).toContain('+ Add to a series');
    });

    it('names the series a filed session is already in', () => {
        expect(mountFiler('Vue Deep Dive').text()).toContain('Vue Deep Dive');
    });

    it('offers the series already running once the field is opened', async () => {
        const wrapper = mountFiler();
        await wrapper.find('[data-testid="edit-series"]').trigger('click');
        await flushPromises();
        await wrapper.find('input').trigger('focus');

        const suggestions = wrapper.findAll('[role="option"]').map((option) => option.text());

        expect(suggestions).toEqual(['Friday Pairing', 'Vue Deep Dive']);
    });

    it('files the session under the name that was typed', async () => {
        const wrapper = mountFiler();
        await wrapper.find('[data-testid="edit-series"]').trigger('click');
        await flushPromises();

        await wrapper.find('input').setValue('Friday Pairing');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(SeriesApi.file).toHaveBeenCalledWith(7, 'Friday Pairing');
        expect(wrapper.emitted('filed')).toHaveLength(1);
    });

    // Emptying the field is how a session is taken back out of a series.
    it('takes the session out of its series when the field is cleared', async () => {
        const wrapper = mountFiler('Vue Deep Dive');
        await wrapper.find('[data-testid="edit-series"]').trigger('click');
        await flushPromises();

        await wrapper.find('input').setValue('   ');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(SeriesApi.file).toHaveBeenCalledWith(7, null);
    });

    it('starts the field on the series the session is already in', async () => {
        const wrapper = mountFiler('Vue Deep Dive');
        await wrapper.find('[data-testid="edit-series"]').trigger('click');
        await flushPromises();

        expect(wrapper.find<HTMLInputElement>('input').element.value).toBe('Vue Deep Dive');
    });

    // The row is the only place this can be done, so a failure must not look like a success.
    it('keeps the field open and says so when filing fails', async () => {
        SeriesApi.file = vi.fn().mockRejectedValue(new Error('nope'));
        const wrapper = mountFiler();
        await wrapper.find('[data-testid="edit-series"]').trigger('click');
        await flushPromises();

        await wrapper.find('input').setValue('Friday Pairing');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(wrapper.find('[role="alert"]').exists()).toBe(true);
        expect(wrapper.find('form').exists()).toBe(true);
        expect(wrapper.emitted('filed')).toBeUndefined();
    });

    it('leaves the session alone when the field is dismissed', async () => {
        const wrapper = mountFiler('Vue Deep Dive');
        await wrapper.find('[data-testid="edit-series"]').trigger('click');
        await flushPromises();

        await wrapper.find('input').setValue('Something else');
        await wrapper.find('[data-testid="cancel-series"]').trigger('click');

        expect(SeriesApi.file).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('Vue Deep Dive');
    });
});
