import type { INotification, INotificationGrowthSession } from '@/types';

/**
 * Turns a notification into the sentence a reader sees.
 *
 * Every part of a notification is nullable, and a deletion is served from a snapshot that may
 * predate the column holding it. So each sentence is built from clauses that drop out when their
 * data is missing, rather than from one template — the reader gets a shorter true sentence instead
 * of a complete one with "null" in it.
 */

/** Stands in for an initiator the payload did not carry. */
const SOMEONE = 'Someone';

/** Stands in for a growth session that can no longer describe itself. */
const A_GROWTH_SESSION = 'a growth session';

/**
 * Who the sentence is about. Exported so anything else labelling the initiator — the avatar beside
 * the sentence, for one — falls back to the same name and cannot disagree with the sentence itself.
 */
export function initiatorName(notification: INotification): string {
    return notification.initiator?.name || SOMEONE;
}

export function notificationSentence(notification: INotification): string {
    const who = initiatorName(notification);
    const what = notification.growth_session?.title || A_GROWTH_SESSION;
    const when = timeRange(notification.growth_session);
    const where = notification.growth_session?.location;

    switch (notification.type) {
        case 'gs_comment':
            return `${who} commented on ${what}`;

        case 'gs_time':
            return join(`${who} updated the time of ${what}`, when);

        case 'gs_location':
            return join(`${who} changed the location of ${what}`, where && `to ${where}`);

        case 'gs_time_location':
            return join(`${who} updated the time and location of ${what}`, when, where && `now at ${where}`);

        case 'gs_deleted':
            return `${who} deleted ${what}`;

        default:
            // A type this build has not been taught yet still says who did something to what.
            return `${who} updated ${what}`;
    }
}

/** "from 03:30 pm to 05:00 pm", or nothing when either end is missing. */
function timeRange(growthSession: INotificationGrowthSession | null): string | null {
    if (!growthSession?.start_time || !growthSession?.end_time) return null;

    return `from ${growthSession.start_time} to ${growthSession.end_time}`;
}

/** Appends whichever clauses survived, comma-separating anything after the first. */
function join(subject: string, ...clauses: (string | null | undefined | false)[]): string {
    const present = clauses.filter((clause): clause is string => Boolean(clause));

    if (!present.length) return subject;

    const [first, ...rest] = present;

    return rest.length ? `${subject} ${first}, ${rest.join(', ')}` : `${subject} ${first}`;
}
