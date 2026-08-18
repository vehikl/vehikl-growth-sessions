import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/**
 * The current time, refreshed on an interval, for anything rendering how long ago something was.
 *
 * One clock per component rather than one timer per label: everything reading it re-derives
 * together, and there is a single thing to stop when the component goes away.
 */
export function useNow(everyMs = 60_000): Ref<number> {
    const now = ref(Date.now());
    let ticking: ReturnType<typeof setInterval> | undefined;

    onMounted(() => {
        ticking = setInterval(() => (now.value = Date.now()), everyMs);
    });

    // An interval outlives the component that started it, and this one holds a closure over a ref.
    onBeforeUnmount(() => clearInterval(ticking));

    return now;
}
