import { SESSION_TIME_ZONE, inViewerTimeZone, sessionMoment, viewerDayOffset } from '@/lib/timezone';
import moment from 'moment-timezone';
import { afterEach, describe, expect, it, vi } from 'vitest';

/** Pretend the person looking at the page is somewhere other than the test runner's zone. */
function viewingFrom(zone: string) {
    vi.spyOn(moment.tz, 'guess').mockReturnValue(zone);
}

afterEach(() => {
    vi.restoreAllMocks();
});

describe('sessionMoment', () => {
    it('reads a stored 12-hour time as Eastern wall-clock time', () => {
        expect(sessionMoment('2024-06-15', '03:30 pm').toISOString()).toBe('2024-06-15T19:30:00.000Z');
    });

    it('reads a stored 24-hour time the same way', () => {
        expect(sessionMoment('2024-06-15', '15:30').toISOString()).toBe('2024-06-15T19:30:00.000Z');
    });

    it('follows the session zone through its own daylight saving change', () => {
        // The same wall-clock reading is five hours behind UTC in January and four in June.
        expect(sessionMoment('2024-01-15', '03:30 pm').toISOString()).toBe('2024-01-15T20:30:00.000Z');
    });

    it('is invalid when the time is missing', () => {
        expect(sessionMoment('2024-06-15', '').isValid()).toBe(false);
    });
});

describe('inViewerTimeZone', () => {
    it('leaves the reading alone for a viewer already in the session zone', () => {
        viewingFrom(SESSION_TIME_ZONE);

        expect(inViewerTimeZone('2024-06-15', '03:30 pm').format('hh:mm a')).toBe('03:30 pm');
    });

    it('shifts the reading to the clock the viewer is on', () => {
        viewingFrom('America/Vancouver');

        expect(inViewerTimeZone('2024-06-15', '03:30 pm').format('hh:mm a')).toBe('12:30 pm');
    });

    it('crosses the date line when the viewer is far enough ahead', () => {
        viewingFrom('Asia/Tokyo');

        expect(inViewerTimeZone('2024-06-15', '03:30 pm').format('YYYY-MM-DD hh:mm a')).toBe('2024-06-16 04:30 am');
    });
});

describe('viewerDayOffset', () => {
    it('is zero when the session falls on the same day for the viewer', () => {
        viewingFrom('America/Vancouver');

        expect(viewerDayOffset('2024-06-15', '03:30 pm')).toBe(0);
    });

    it('is one when the viewer reads the session as the next day', () => {
        viewingFrom('Asia/Tokyo');

        expect(viewerDayOffset('2024-06-15', '03:30 pm')).toBe(1);
    });

    it('is minus one when the viewer reads a morning session as the previous day', () => {
        viewingFrom('Pacific/Honolulu');

        expect(viewerDayOffset('2024-06-15', '05:00 am')).toBe(-1);
    });

    it('is not thrown off by a daylight saving change on either clock', () => {
        viewingFrom('Asia/Tokyo');

        // This session lands on the day the North American clocks spring forward.
        expect(viewerDayOffset('2024-03-10', '11:00 pm')).toBe(1);
    });

    it('is zero for a time that cannot be read at all', () => {
        viewingFrom('Asia/Tokyo');

        expect(viewerDayOffset('2024-06-15', '')).toBe(0);
    });
});
