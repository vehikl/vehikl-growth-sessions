import { DateTime } from '@/classes/DateTime';
import { ref } from 'vue';

export type BoardView = 'week' | 'day';

/** The slice of `window` this composable touches, so tests can pass a fake. */
export interface BoardUrlWindow {
    location: { search: string; pathname: string };
    history: Pick<History, 'pushState'>;
    addEventListener(type: 'popstate', listener: () => void): void;
    removeEventListener(type: 'popstate', listener: () => void): void;
}

/** Stands in for `window` where there is none: during SSR nothing reads or writes the url. */
const noBrowserWindow: BoardUrlWindow = {
    location: { search: '', pathname: '/' },
    history: { pushState: () => {} },
    addEventListener: () => {},
    removeEventListener: () => {},
};

/**
 * Owns the board's query string — `?date`, `?view` and `?session` — as reactive state.
 * `URLSearchParams`, `pushState` and the popstate listener are implementation detail: callers
 * read the refs, call the setters, and let `watchUrl` tell them when a history navigation
 * has rewritten all three at once.
 *
 * Deliberately free of Vue lifecycle hooks, so it is callable straight from a test.
 */
export function useBoardUrlState(win: BoardUrlWindow = typeof window === 'undefined' ? noBrowserWindow : window) {
    /** The week the board is showing. */
    const referenceDate = ref(DateTime.today());
    /**
     * The view the url asks for. It is *requested* rather than active because the board may
     * refuse it — week view needs a wide enough screen — and that clamp is the caller's call.
     */
    const requestedView = ref<BoardView>('day');
    /** The session whose detail drawer is open, if the url names one. */
    const sessionId = ref<number | null>(null);

    function currentParams(): URLSearchParams {
        return new URLSearchParams(win.location.search);
    }

    function commit(params: URLSearchParams): void {
        const query = params.toString();
        win.history.pushState({}, '', query ? `?${query}` : win.location.pathname);
    }

    /** Adopt every board parameter in the current url, falling back to the defaults when absent. */
    function syncFromUrl(): void {
        const params = currentParams();

        referenceDate.value = params.has('date') ? DateTime.parseByDate(params.get('date')!) : DateTime.today();
        requestedView.value = params.get('view') === 'week' ? 'week' : 'day';

        const requestedSession = Number(params.get('session'));
        sessionId.value = Number.isInteger(requestedSession) && requestedSession > 0 ? requestedSession : null;
    }

    /** Move the board's week and record the move in the url. */
    function shiftDateBy(deltaDays: number): void {
        const next = DateTime.parseByDate(referenceDate.value.toDateString());
        next.addDays(deltaDays);
        referenceDate.value = next;

        const params = currentParams();
        params.set('date', next.toDateString());
        commit(params);
    }

    /** Ask for a view and record the request in the url. */
    function setView(view: BoardView): void {
        requestedView.value = view;

        const params = currentParams();
        params.set('view', view);
        commit(params);
    }

    /**
     * Open or close a session's drawer in the url, so an invite link (or any copied url) lands
     * on the board with that drawer already open, and the back button closes it again.
     */
    function setSessionId(id: number | null): void {
        sessionId.value = id;

        const params = currentParams();
        const wanted = id ? String(id) : null;
        if (params.get('session') === wanted) return;

        if (wanted) {
            params.set('session', wanted);
        } else {
            params.delete('session');
        }
        commit(params);
    }

    /**
     * Re-read the url whenever the visitor navigates through history, then notify the caller —
     * which by then can trust the refs. Returns the unsubscribe.
     */
    function watchUrl(onChange: () => void): () => void {
        const listener = () => {
            syncFromUrl();
            onChange();
        };

        win.addEventListener('popstate', listener);
        return () => win.removeEventListener('popstate', listener);
    }

    return { referenceDate, requestedView, sessionId, syncFromUrl, shiftDateBy, setView, setSessionId, watchUrl };
}
