import { relativeTime } from '@/lib/relativeTime';

const NOW = Date.parse('2026-08-18T12:00:00.000Z');

/** A timestamp the given number of milliseconds before NOW, in the shape the API sends. */
function agoBy(milliseconds: number): string {
    return new Date(NOW - milliseconds).toISOString();
}

const SECOND = 1_000;
const MINUTE = 60 * SECOND;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

describe('relativeTime', () => {
    it('counts minutes up to the hour', () => {
        expect(relativeTime(agoBy(MINUTE), NOW)).toBe('1 minute ago');
        expect(relativeTime(agoBy(2 * MINUTE), NOW)).toBe('2 minutes ago');
        expect(relativeTime(agoBy(59 * MINUTE), NOW)).toBe('59 minutes ago');
    });

    it('counts hours up to the day', () => {
        expect(relativeTime(agoBy(HOUR), NOW)).toBe('1 hour ago');
        expect(relativeTime(agoBy(2 * HOUR), NOW)).toBe('2 hours ago');
        expect(relativeTime(agoBy(23 * HOUR), NOW)).toBe('23 hours ago');
    });

    it('counts days after that', () => {
        expect(relativeTime(agoBy(DAY), NOW)).toBe('1 day ago');
        expect(relativeTime(agoBy(2 * DAY), NOW)).toBe('2 days ago');
        expect(relativeTime(agoBy(400 * DAY), NOW)).toBe('400 days ago');
    });

    it('says just now for anything under a minute', () => {
        expect(relativeTime(agoBy(0), NOW)).toBe('just now');
        expect(relativeTime(agoBy(59 * SECOND), NOW)).toBe('just now');
    });

    // Each unit has to hand over on the exact boundary, or a label skips or repeats as it ticks.
    it('hands over between units on the boundary', () => {
        expect(relativeTime(agoBy(MINUTE - 1), NOW)).toBe('just now');
        expect(relativeTime(agoBy(HOUR - 1), NOW)).toBe('59 minutes ago');
        expect(relativeTime(agoBy(HOUR), NOW)).toBe('1 hour ago');
        expect(relativeTime(agoBy(DAY - 1), NOW)).toBe('23 hours ago');
        expect(relativeTime(agoBy(DAY), NOW)).toBe('1 day ago');
    });

    // Server and browser clocks disagree by seconds, so a notification can arrive from the future.
    it('does not count backwards for a timestamp ahead of the clock', () => {
        expect(relativeTime(agoBy(-30 * SECOND), NOW)).toBe('just now');
        expect(relativeTime(agoBy(-2 * HOUR), NOW)).toBe('just now');
    });

    it('says nothing for a timestamp it cannot read', () => {
        expect(relativeTime('not a timestamp', NOW)).toBe('');
    });

    it('reads the microsecond precision the API sends', () => {
        expect(relativeTime('2026-08-18T11:58:00.000000Z', NOW)).toBe('2 minutes ago');
    });
});
