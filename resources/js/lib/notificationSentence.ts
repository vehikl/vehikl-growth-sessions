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
 *
 * The sentence is built as pieces rather than a string, because the parts a reader scans for - who
 * did it, which session, what it says now - have to be told apart from the words joining them.
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
 * The parts of a sentence a reader actually scans for: who did it, which session, and what it says
 * now. Naming them rather than flagging them keeps every decision about how they look in the
 * component - the builder says what a piece is, not how much weight to give it.
 */
export type SegmentRole = 'initiator' | 'title' | 'value';

/** A run of the sentence that reads as one piece. Concatenating them all gives the sentence back. */
export interface NotificationSegment {
    text: string;
    /** Absent for the words that only join the interesting parts together. */
    role?: SegmentRole;
}

/**
 * Who the sentence is about. Exported so anything else labelling the initiator - the avatar beside
 * the sentence, for one - falls back to the same name and cannot disagree with the sentence itself.
 */
export function initiatorName(notification: INotification): string {
    return notification.initiator?.name || SOMEONE;
}

/**
 * The sentence in the pieces a reader distinguishes.
 *
 * A stand-in never takes a role. "Someone" and "a growth session" are what the payload could not
 * tell us, so giving them weight would point the eye at the least informative words in the row.
 */
export function notificationSegments(notification: INotification): NotificationSegment[] {
    const events = notification.event_types ?? [];
    const session = notification.growth_session;

    const who: NotificationSegment = named(initiatorName(notification), notification.initiator?.name, 'initiator');
    const what: NotificationSegment = named(session?.title || A_GROWTH_SESSION, session?.title, 'title');

    // These two describe the notification on their own, so they win over anything else in the list.
    if (events.includes('gs_deleted')) {
        // Nobody can look the session up any more, so the sentence says when it would have been.
        return [who, { text: ' cancelled ' }, what, ...detail('scheduled for', [readableDate(session?.date), timeRange(session)])];
    }

    if (events.includes('gs_comment')) return [who, { text: ' commented on ' }, what];

    const moved = EDIT_AXES.filter((axis) => events.includes(axis.event));

    // Either nothing was reported, or every event in it postdates this build.
    if (!moved.length) return [who, { text: ' updated ' }, what];

    return [
        who,
        { text: ` updated the ${readAsList(moved.map((axis) => axis.noun))} of ` },
        what,
        ...detail(
            'now',
            moved.map((axis) => axis.newValue(session)),
        ),
    ];
}

/** The same sentence as one string - what it reads as, with nothing about how it looks. */
export function notificationSentence(notification: INotification): string {
    return notificationSegments(notification)
        .map((segment) => segment.text)
        .join('');
}

/** Carries a role only when the text is the real thing rather than what stands in for it. */
function named(text: string, real: string | null | undefined, role: SegmentRole): NotificationSegment {
    return real ? { text, role } : { text };
}

/**
 * ", now 03:30 pm to 05:00 pm" as pieces - each value on its own, so the words that introduce and
 * separate them stay quiet. Nothing at all when none of the details survived.
 */
function detail(leadIn: string, details: (string | null)[]): NotificationSegment[] {
    const present = details.filter((value) => value !== null);

    if (!present.length) return [];

    return present.flatMap((value, index) => [{ text: index === 0 ? `, ${leadIn} ` : ', ' }, { text: value, role: 'value' as const }]);
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
