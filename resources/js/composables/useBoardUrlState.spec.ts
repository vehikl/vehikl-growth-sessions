import { DateTime } from '@/classes/DateTime';
import { useBoardUrlState, type BoardUrlWindow } from '@/composables/useBoardUrlState';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const today = '2020-01-15';
const nextWeek = '2020-01-22';

type FakeWindow = BoardUrlWindow & {
    history: { pushState: ReturnType<typeof vi.fn> };
    popstate(): void;
};

/**
 * A window double that behaves like the real one in the way this module depends on:
 * `pushState` is what changes `location.search`, so a read after a write sees the write.
 */
function fakeWindow(search = ''): FakeWindow {
    const listeners: Array<() => void> = [];

    const win: FakeWindow = {
        location: { search, pathname: '/board' },
        history: {
            pushState: vi.fn((_state: unknown, _title: string, url: string) => {
                win.location.search = url.startsWith('?') ? url : '';
            }),
        },
        addEventListener: (_type, listener) => listeners.push(listener),
        removeEventListener: (_type, listener) => {
            const index = listeners.indexOf(listener);
            if (index >= 0) listeners.splice(index, 1);
        },
        popstate: () => listeners.forEach((listener) => listener()),
    };

    return win;
}

describe('useBoardUrlState', () => {
    beforeEach(() => {
        DateTime.setTestNow(today);
    });

    afterEach(() => {
        DateTime.setTestNow(new Date().toISOString());
    });

    it('starts on today, in day view, with no session open', () => {
        const { referenceDate, requestedView, sessionId } = useBoardUrlState(fakeWindow());

        expect(referenceDate.value.toDateString()).toBe(today);
        expect(requestedView.value).toBe('day');
        expect(sessionId.value).toBeNull();
    });

    describe('syncFromUrl', () => {
        it('adopts every parameter the board owns', () => {
            const { referenceDate, requestedView, sessionId, syncFromUrl } = useBoardUrlState(fakeWindow(`?date=${nextWeek}&view=week&session=42`));

            syncFromUrl();

            expect(referenceDate.value.toDateString()).toBe(nextWeek);
            expect(requestedView.value).toBe('week');
            expect(sessionId.value).toBe(42);
        });

        it('falls back to today when the query string has no date', () => {
            const { referenceDate, syncFromUrl } = useBoardUrlState(fakeWindow('?somethingElse=1'));

            syncFromUrl();

            expect(referenceDate.value.toDateString()).toBe(today);
        });

        it('falls back to day view for an unrecognised view', () => {
            const { requestedView, syncFromUrl } = useBoardUrlState(fakeWindow('?view=agenda'));

            syncFromUrl();

            expect(requestedView.value).toBe('day');
        });

        it('reports no session for a non-numeric session parameter', () => {
            const { sessionId, syncFromUrl } = useBoardUrlState(fakeWindow('?session=not-a-number'));

            syncFromUrl();

            expect(sessionId.value).toBeNull();
        });

        it('clears state that has dropped out of the url', () => {
            const win = fakeWindow('?view=week&session=42');
            const { requestedView, sessionId, syncFromUrl } = useBoardUrlState(win);
            syncFromUrl();

            win.location.search = '';
            syncFromUrl();

            expect(requestedView.value).toBe('day');
            expect(sessionId.value).toBeNull();
        });
    });

    describe('shiftDateBy', () => {
        it('moves the date forward', () => {
            const { referenceDate, shiftDateBy } = useBoardUrlState(fakeWindow());

            shiftDateBy(7);

            expect(referenceDate.value.toDateString()).toBe(nextWeek);
        });

        it('moves the date backward', () => {
            const { referenceDate, shiftDateBy } = useBoardUrlState(fakeWindow());

            shiftDateBy(-7);

            expect(referenceDate.value.toDateString()).toBe('2020-01-08');
        });

        it('records the new date in the url', () => {
            const win = fakeWindow();
            const { shiftDateBy } = useBoardUrlState(win);

            shiftDateBy(7);

            expect(win.history.pushState).toHaveBeenCalledWith({}, '', `?date=${nextWeek}`);
        });

        it('preserves other query parameters', () => {
            const win = fakeWindow('?view=week');
            const { shiftDateBy } = useBoardUrlState(win);

            shiftDateBy(7);

            expect(win.history.pushState).toHaveBeenCalledWith({}, '', `?view=week&date=${nextWeek}`);
        });

        it('shifts from the date already in the url rather than from today', () => {
            const win = fakeWindow(`?date=${nextWeek}`);
            const { referenceDate, syncFromUrl, shiftDateBy } = useBoardUrlState(win);

            syncFromUrl();
            shiftDateBy(7);

            expect(referenceDate.value.toDateString()).toBe('2020-01-29');
            expect(win.history.pushState).toHaveBeenCalledWith({}, '', '?date=2020-01-29');
        });
    });

    describe('setView', () => {
        it('records the view in the url while preserving the date', () => {
            const win = fakeWindow(`?date=${today}`);
            const { requestedView, setView } = useBoardUrlState(win);

            setView('week');

            expect(requestedView.value).toBe('week');
            expect(win.history.pushState).toHaveBeenCalledWith({}, '', `?date=${today}&view=week`);
        });

        it('records a switch back to the day view', () => {
            const win = fakeWindow('?view=week');
            const { requestedView, setView } = useBoardUrlState(win);

            setView('day');

            expect(requestedView.value).toBe('day');
            expect(win.location.search).toBe('?view=day');
        });
    });

    describe('setSessionId', () => {
        it('records the open session in the url', () => {
            const win = fakeWindow(`?date=${today}`);
            const { sessionId, setSessionId } = useBoardUrlState(win);

            setSessionId(42);

            expect(sessionId.value).toBe(42);
            expect(win.history.pushState).toHaveBeenCalledWith({}, '', `?date=${today}&session=42`);
        });

        it('drops the session from the url while keeping the rest', () => {
            const win = fakeWindow(`?date=${today}&session=42`);
            const { sessionId, syncFromUrl, setSessionId } = useBoardUrlState(win);
            syncFromUrl();

            setSessionId(null);

            expect(sessionId.value).toBeNull();
            expect(win.history.pushState).toHaveBeenCalledWith({}, '', `?date=${today}`);
        });

        it('falls back to the path when closing the session empties the query string', () => {
            const win = fakeWindow('?session=42');
            const { setSessionId } = useBoardUrlState(win);

            setSessionId(null);

            expect(win.history.pushState).toHaveBeenCalledWith({}, '', '/board');
        });

        it('does not push a history entry when the url already names that session', () => {
            const win = fakeWindow('?session=42');
            const { sessionId, syncFromUrl, setSessionId } = useBoardUrlState(win);
            syncFromUrl();

            setSessionId(42);

            expect(win.history.pushState).not.toHaveBeenCalled();
            expect(sessionId.value).toBe(42);
        });

        it('does not push a history entry when closing an already closed session', () => {
            const win = fakeWindow(`?date=${today}`);
            const { setSessionId } = useBoardUrlState(win);

            setSessionId(null);

            expect(win.history.pushState).not.toHaveBeenCalled();
        });
    });

    describe('watchUrl', () => {
        it('re-reads the url and notifies the caller on a history navigation', () => {
            const win = fakeWindow();
            const { referenceDate, requestedView, sessionId, watchUrl } = useBoardUrlState(win);
            const onChange = vi.fn();
            watchUrl(onChange);

            win.location.search = `?date=${nextWeek}&view=week&session=42`;
            win.popstate();

            expect(referenceDate.value.toDateString()).toBe(nextWeek);
            expect(requestedView.value).toBe('week');
            expect(sessionId.value).toBe(42);
            expect(onChange).toHaveBeenCalledOnce();
        });

        it('syncs before notifying, so the caller sees the new url', () => {
            const win = fakeWindow();
            const { referenceDate, watchUrl } = useBoardUrlState(win);
            const seen: string[] = [];
            watchUrl(() => seen.push(referenceDate.value.toDateString()));

            win.location.search = `?date=${nextWeek}`;
            win.popstate();

            expect(seen).toEqual([nextWeek]);
        });

        it('stops notifying once the returned unsubscribe is called', () => {
            const win = fakeWindow();
            const { watchUrl } = useBoardUrlState(win);
            const onChange = vi.fn();

            watchUrl(onChange)();
            win.popstate();

            expect(onChange).not.toHaveBeenCalled();
        });
    });
});
