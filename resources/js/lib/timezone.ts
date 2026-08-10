import moment, { Moment } from 'moment-timezone';

export const SESSION_TIME_ZONE = 'America/Toronto';

const STORED_TIME_FORMATS = ['YYYY-MM-DD hh:mm a', 'YYYY-MM-DD h:mm a', 'YYYY-MM-DD HH:mm', 'YYYY-MM-DD HH:mm:ss'];

export function viewerTimeZone(): string {
    return moment.tz.guess();
}

export function sessionMoment(date: string, time: string): Moment {
    return moment.tz(`${date} ${time}`, STORED_TIME_FORMATS, true, SESSION_TIME_ZONE);
}

export function inViewerTimeZone(date: string, time: string): Moment {
    return sessionMoment(date, time).tz(viewerTimeZone());
}

/**
 * How many days the viewer's reading of a session differs from the date it is filed under — a late
 * afternoon session in Toronto is already tomorrow in Tokyo, and the board still lists it under the
 * Toronto day. Compared as UTC midnights so a clock change on either side cannot round the gap away.
 */
export function viewerDayOffset(date: string, time: string): number {
    const asViewerSeesIt = inViewerTimeZone(date, time);

    if (!asViewerSeesIt.isValid()) {
        return 0;
    }

    return moment.utc(asViewerSeesIt.format('YYYY-MM-DD'), 'YYYY-MM-DD').diff(moment.utc(date, 'YYYY-MM-DD'), 'days');
}
