import { DateTime } from '@/classes/DateTime';
import { useReferenceDate, type ReferenceDateWindow } from '@/composables/useReferenceDate';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const today = '2020-01-15';
const nextWeek = '2020-01-22';

function fakeWindow(search = ''): ReferenceDateWindow & { history: { pushState: ReturnType<typeof vi.fn> } } {
    return {
        location: { search },
        history: { pushState: vi.fn() },
    };
}

describe('useReferenceDate', () => {
    beforeEach(() => {
        DateTime.setTestNow(today);
    });

    afterEach(() => {
        DateTime.setTestNow(new Date().toISOString());
    });

    it('starts at today', () => {
        const { referenceDate } = useReferenceDate(fakeWindow());

        expect(referenceDate.value.toDateString()).toBe(today);
    });

    describe('syncFromUrl', () => {
        it('adopts the date in the query string', () => {
            const { referenceDate, syncFromUrl } = useReferenceDate(fakeWindow(`?date=${nextWeek}`));

            syncFromUrl();

            expect(referenceDate.value.toDateString()).toBe(nextWeek);
        });

        it('falls back to today when the query string has no date', () => {
            const { referenceDate, syncFromUrl } = useReferenceDate(fakeWindow('?somethingElse=1'));

            syncFromUrl();

            expect(referenceDate.value.toDateString()).toBe(today);
        });
    });

    describe('shiftBy', () => {
        it('moves the reference date forward', () => {
            const { referenceDate, shiftBy } = useReferenceDate(fakeWindow());

            shiftBy(7);

            expect(referenceDate.value.toDateString()).toBe(nextWeek);
        });

        it('moves the reference date backward', () => {
            const { referenceDate, shiftBy } = useReferenceDate(fakeWindow());

            shiftBy(-7);

            expect(referenceDate.value.toDateString()).toBe('2020-01-08');
        });

        it('records the new date in the url', () => {
            const win = fakeWindow();
            const { shiftBy } = useReferenceDate(win);

            shiftBy(7);

            expect(win.history.pushState).toHaveBeenCalledWith({}, '', `?date=${nextWeek}`);
        });

        it('preserves other query parameters when recording the new date', () => {
            const win = fakeWindow('?view=week');
            const { shiftBy } = useReferenceDate(win);

            shiftBy(7);

            expect(win.history.pushState).toHaveBeenCalledWith({}, '', `?view=week&date=${nextWeek}`);
        });

        it('moves the reference date without touching history when the caller pushes its own url', () => {
            const win = fakeWindow();
            const { referenceDate, shiftBy } = useReferenceDate(win);

            shiftBy(7, { pushUrl: false });

            expect(referenceDate.value.toDateString()).toBe(nextWeek);
            expect(win.history.pushState).not.toHaveBeenCalled();
        });

        it('shifts from the date already in the url rather than from today', () => {
            const win = fakeWindow(`?date=${nextWeek}`);
            const { referenceDate, syncFromUrl, shiftBy } = useReferenceDate(win);

            syncFromUrl();
            shiftBy(7);

            expect(referenceDate.value.toDateString()).toBe('2020-01-29');
            expect(win.history.pushState).toHaveBeenCalledWith({}, '', '?date=2020-01-29');
        });
    });
});
