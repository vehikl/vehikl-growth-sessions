import { DateTime } from '@/classes/DateTime';
import { ref } from 'vue';

/** The slice of `window` this composable touches, so tests can pass a fake. */
export interface ReferenceDateWindow {
    location: { search: string };
    history: Pick<History, 'pushState'>;
}

/**
 * Owns the week the board is showing, keeping it in sync with the `?date=` query
 * string. Deliberately free of lifecycle hooks — the caller decides when to sync
 * and wires up popstate — so this is callable straight from a test.
 */
export function useReferenceDate(win: ReferenceDateWindow = window) {
    const referenceDate = ref(DateTime.today());

    /** Adopt the date in the current URL, falling back to today when absent. */
    function syncFromUrl(): void {
        const urlSearchParams = new URLSearchParams(win.location.search);
        referenceDate.value = urlSearchParams.has('date') ? DateTime.parseByDate(urlSearchParams.get('date')!) : DateTime.today();
    }

    /**
     * Move the reference date and record the move in the URL. Pass `pushUrl: false` when the
     * caller is changing other query parameters in the same gesture and wants to write them
     * all in one history entry — otherwise the move costs the visitor two presses of Back.
     */
    function shiftBy(deltaDays: number, { pushUrl = true }: { pushUrl?: boolean } = {}): void {
        const next = DateTime.parseByDate(referenceDate.value.toDateString());
        next.addDays(deltaDays);
        referenceDate.value = next;

        if (!pushUrl) return;

        const urlSearchParams = new URLSearchParams(win.location.search);
        urlSearchParams.set('date', referenceDate.value.toDateString());
        win.history.pushState({}, '', `?${urlSearchParams.toString()}`);
    }

    return { referenceDate, syncFromUrl, shiftBy };
}
