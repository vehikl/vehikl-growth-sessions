import { useCopyStatus } from '@/composables/useCopyStatus';
import { mount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';

const copyToClipboard = vi.hoisted(() => vi.fn());
vi.mock('@/lib/clipboard', () => ({ copyToClipboard }));

/** `onUnmounted` needs a component, so the composable is exercised through a bare host. */
function mountHost(clearAfterMs?: number) {
    let api!: ReturnType<typeof useCopyStatus>;

    const wrapper = mount(
        defineComponent({
            setup() {
                api = useCopyStatus(clearAfterMs);
                return () => null;
            },
        }),
    );

    return { wrapper, ...api };
}

describe('useCopyStatus', () => {
    beforeEach(() => {
        // Only the timers are faked, so `flushPromises` still settles the copy itself.
        vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout'] });
        copyToClipboard.mockReset().mockResolvedValue(true);
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('starts with nothing to report', () => {
        const { status } = mountHost();

        expect(status.value).toBeNull();
    });

    it('reports a successful copy', async () => {
        const { status, copy } = mountHost();

        await copy('https://growth.vehikl.com');
        await flushPromises();

        expect(copyToClipboard).toHaveBeenCalledWith('https://growth.vehikl.com');
        expect(status.value).toBe('copied');
    });

    it('reports a failed copy', async () => {
        copyToClipboard.mockResolvedValue(false);
        const { status, copy } = mountHost();

        await copy('https://growth.vehikl.com');
        await flushPromises();

        expect(status.value).toBe('failed');
    });

    it('clears itself, so a view left open does not keep claiming a stale copy', async () => {
        const { status, copy } = mountHost();

        await copy('https://growth.vehikl.com');
        await flushPromises();
        vi.advanceTimersByTime(5000);

        expect(status.value).toBeNull();
    });

    it('honours a caller-chosen delay', async () => {
        const { status, copy } = mountHost(100);

        await copy('https://growth.vehikl.com');
        await flushPromises();
        vi.advanceTimersByTime(99);
        expect(status.value).toBe('copied');

        vi.advanceTimersByTime(1);
        expect(status.value).toBeNull();
    });

    it('restarts the countdown on a second copy rather than stacking timers', async () => {
        const { status, copy } = mountHost();

        await copy('https://growth.vehikl.com');
        await flushPromises();
        vi.advanceTimersByTime(4000);

        await copy('https://growth.vehikl.com');
        await flushPromises();
        vi.advanceTimersByTime(4000);

        expect(status.value).toBe('copied');
    });

    it('drops its pending timer when the view goes away', async () => {
        const { wrapper, status, copy } = mountHost();

        await copy('https://growth.vehikl.com');
        await flushPromises();
        wrapper.unmount();
        vi.advanceTimersByTime(5000);

        expect(status.value).toBe('copied');
    });
});
