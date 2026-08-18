import moment from 'moment-timezone';

/**
 * How long ago something happened, in the coarsest unit that still says something useful.
 *
 * Takes the current time as an argument rather than reading the clock itself, so a caller can
 * re-derive every label from one tick and a test can say what time it is.
 */

const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

export function relativeTime(timestamp: string, now: number): string {
    const happenedAt = moment(timestamp);

    if (!happenedAt.isValid()) return '';

    const elapsed = now - happenedAt.valueOf();

    // Also covers a timestamp from the future, which a clock a few seconds out will produce.
    if (elapsed < MINUTE) return 'just now';

    if (elapsed < HOUR) return ago(Math.floor(elapsed / MINUTE), 'minute');

    if (elapsed < DAY) return ago(Math.floor(elapsed / HOUR), 'hour');

    return ago(Math.floor(elapsed / DAY), 'day');
}

function ago(count: number, unit: string): string {
    return `${count} ${unit}${count === 1 ? '' : 's'} ago`;
}
