import { copyToClipboard } from '@/lib/clipboard';
import { onUnmounted, ref } from 'vue';

export type CopyStatus = 'copied' | 'failed' | null;

/**
 * A copy-to-clipboard confirmation that clears itself, so a view left open does not keep
 * claiming a stale copy. Repeated copies restart the countdown rather than stacking timers.
 */
export function useCopyStatus(clearAfterMs = 5000) {
    const status = ref<CopyStatus>(null);
    let timer: ReturnType<typeof setTimeout> | undefined;

    async function copy(text: string): Promise<void> {
        status.value = (await copyToClipboard(text)) ? 'copied' : 'failed';

        clearTimeout(timer);
        timer = setTimeout(() => (status.value = null), clearAfterMs);
    }

    onUnmounted(() => clearTimeout(timer));

    return { status, copy };
}
