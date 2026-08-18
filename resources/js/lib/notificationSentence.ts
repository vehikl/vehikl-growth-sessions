import type { INotification, INotificationGrowthSession, NotificationType } from '@/types';
import moment from 'moment-timezone';

/**
 * Turns a notification into the sentence a reader sees.
 *
 * A notification reports a list of events, so the sentence is composed rather than looked up: the
 * subject names everything that moved and the tail gives the new values, in the same order. That is
 * what lets a new event be one row in the table below instead of a new sentence per combination.
 *
 * Every part of a notification is nullable, and a session is described from a snapshot that may
 * predate the column holding it. So each clause drops out when its data is missing, rather than
 * being filled with "null" - the reader gets a shorter true sentence instead of a complete false one.
 */

/** Stands in for an initiator the payload did not carry. */
const SOMEONE = 'Someone';

/** Stands in for a growth session that can no longer describe itself. */
const A_GROWTH_SESSION = 'a growth session';

interface EditAxis {
    event: NotificationType;
    /** What the sentence calls it: "updated the {noun} of ...". */
    noun: string;
    /** What it moved to, or null when the snapshot cannot say. */
    newValue: (growthSession: INotificationGrowthSession | null) => string | null;
}

/**
 * The events an edit can report, in the order a reader says them. Adding an axis is adding a row -
 * nothing below this reads an event name, so there is no second place to keep in step.
 */
const EDIT_AXES: EditAxis[] = [
    {
        event: 'gs_date',
        noun: 'date',
        newValue: (growthSession) => readableDate(growthSession?.date),
    },
    {
        event: 'gs_time',
        noun: 'time',
        newValue: (growthSession) => timeRange(growthSession),
    },
    {
        event: 'gs_location',
        noun: 'location',
        newValue: (growthSession) => (growthSession?.location ? `at ${growthSession.location}` : null),
    },
];

/**
 * Who the sentence is about. Exported so anything else labelling the initiator - the avatar beside
 * the sentence, for one - falls back to the same name and cannot disagree with the sentence itself.
 */
export function initiatorName(notification: INotification): string {
    return notification.initiator?.name || SOMEONE;
}

export function notificationSentence(notification: INotification): string {
    const events = notification.event_types ?? [];
    const who = initiatorName(notification);
    const what = notification.growth_session?.title || A_GROWTH_SESSION;

    // These two describe the notification on their own, so they win over anything else in the list.
    if (events.includes('gs_deleted')) {
        // Nobody can look the session up any more, so the sentence says when it would have been.
        const session = notification.growth_session;

        return withDetail(`${who} deleted ${what}`, 'scheduled for', [readableDate(session?.date), timeRange(session)]);
    }

    if (events.includes('gs_comment')) return `${who} commented on ${what}`;

    const moved = EDIT_AXES.filter((axis) => events.includes(axis.event));

    // Either nothing was reported, or every event in it postdates this build.
    if (!moved.length) return `${who} updated ${what}`;

    const subject = `${who} updated the ${readAsList(moved.map((axis) => axis.noun))} of ${what}`;

    return withDetail(
        subject,
        'now',
        moved.map((axis) => axis.newValue(notification.growth_session)),
    );
}

/** The subject, then whichever details survived - or just the subject when none of them did. */
function withDetail(subject: string, leadIn: string, details: (string | null)[]): string {
    const present = details.filter((detail) => detail !== null);

    return present.length ? `${subject}, ${leadIn} ${present.join(', ')}` : subject;
}

/** "03:30 pm to 05:00 pm". Half a range is worse than no range, so both ends or neither. */
function timeRange(growthSession: INotificationGrowthSession | null | undefined): string | null {
    return growthSession?.start_time && growthSession?.end_time ? `${growthSession.start_time} to ${growthSession.end_time}` : null;
}

/** "Aug 20" from the payload's Y-m-d, or nothing if it is not a date after all. */
function readableDate(date: string | null | undefined): string | null {
    if (!date) return null;

    const parsed = moment(date, 'YYYY-MM-DD', true);

    return parsed.isValid() ? parsed.format('MMM D') : null;
}

/** "date", "date and time", "date, time and location". */
function readAsList(nouns: string[]): string {
    if (nouns.length < 2) return nouns[0] ?? '';

    return `${nouns.slice(0, -1).join(', ')} and ${nouns[nouns.length - 1]}`;
}
