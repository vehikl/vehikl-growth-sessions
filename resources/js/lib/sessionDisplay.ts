import { GrowthSession } from '@/classes/GrowthSession';
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
    if (session.hasAlreadyHappened) {
        return 'finished';
    }

    const now = moment();
    const start = moment(`${session.date} ${session.start_time}`, ['YYYY-MM-DD hh:mm a', 'YYYY-MM-DD HH:mm']);
    const end = moment(`${session.date} ${session.end_time}`, ['YYYY-MM-DD hh:mm a', 'YYYY-MM-DD HH:mm']);

    if (start.isValid() && end.isValid() && now.isSameOrAfter(start) && now.isSameOrBefore(end)) {
        return 'live';
    }

    return 'upcoming';
}

export function statusMeta(status: SessionStatus): {
    color: string;
    label: string;
} {
    switch (status) {
        case 'live':
            return { color: 'var(--color-gs-live)', label: 'LIVE' };
        case 'upcoming':
            return { color: 'var(--color-gs-upcoming)', label: 'UPCOMING' };
        default:
            return { color: 'var(--color-gs-finished)', label: 'FINISHED' };
    }
}

/** Human capacity label, e.g. "2/4" or "3 joined" when limitless. */
export function capacityLabel(session: GrowthSession): string {
    const joined = session.attendees.length;
    return session.isLimitless ? `${joined}` : `${joined}/${session.attendee_limit}`;
}
