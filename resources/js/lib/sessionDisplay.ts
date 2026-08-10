import { GrowthSession } from '@/classes/GrowthSession';
import { sessionMoment } from '@/lib/timezone';
import moment from 'moment-timezone';

export type SessionStatus = 'live' | 'upcoming' | 'finished';

const AVATAR_PALETTE = ['#e0632a', '#3f6fb5', '#3fb56d', '#b53f8f', '#b58f3f', '#3fb5a9'];

/** Deterministic avatar background colour derived from a name (matches the design mock). */
export function avatarColor(name: string): string {
    let sum = 0;
    for (let i = 0; i < name.length; i++) {
        sum += name.charCodeAt(i);
    }
    return AVATAR_PALETTE[sum % AVATAR_PALETTE.length];
}

/** Derive a live/upcoming/finished status from a session's date and time window. */
export function sessionStatus(session: GrowthSession): SessionStatus {
    const now = moment();
    const start = sessionMoment(session.date, session.start_time);
    const end = sessionMoment(session.date, session.end_time);

    // A session is finished once its end time has passed — including earlier today, not just past days.
    if (end.isValid()) {
        if (now.isAfter(end)) return 'finished';
        if (start.isValid() && now.isSameOrAfter(start)) return 'live';
        return 'upcoming';
    }

    // Fallback when the times can't be parsed: fall back to the date only.
    return session.hasAlreadyHappened ? 'finished' : 'upcoming';
}

export function statusMeta(status: SessionStatus): { color: string; label: string } {
    switch (status) {
        case 'live':
            return { color: 'var(--color-gs-live)', label: 'LIVE' };
        case 'upcoming':
            return { color: 'var(--color-gs-upcoming)', label: 'UPCOMING' };
        default:
            return { color: 'var(--color-gs-finished)', label: 'FINISHED' };
    }
}

/** Human capacity label: just the count when limitless, otherwise "2/4". */
export function capacityLabel(session: GrowthSession): string {
    const joined = session.attendees.length;
    return session.isLimitless ? `${joined}` : `${joined}/${session.attendee_limit}`;
}
