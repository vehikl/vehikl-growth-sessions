import { GrowthSession } from '@/classes/GrowthSession';
import { sessionMoment } from '@/lib/timezone';
import { IUser } from '@/types';
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

export type SessionActionKind = 'join' | 'join-waitlist' | 'watch' | 'leave' | 'edit' | 'delete';

export interface ISessionAction {
    kind: SessionActionKind;
    label: string;
    /** The class each renderer has always hung on this button, kept as the hook tests reach for. */
    hook: string;
}

/**
 * What this viewer may do about this session, in the order the buttons read.
 *
 * The board card, the day view and the detail drawer all offer the same choices and differ only in
 * how they dress them, so the choosing happens once, here. Deriving it per renderer is what let the
 * `Full` badge's two conditions cancel each other out unnoticed - the rule was stated three times
 * and agreed with itself nowhere.
 */
export function sessionActions(session: GrowthSession, user?: IUser | null): ISessionAction[] {
    const actions: ISessionAction[] = [];

    if (session.canJoin(user)) {
        actions.push({ kind: 'join', label: 'Join', hook: 'join-button' });
    }

    // The other half of canJoin: a session with no seat left is something to queue for, never both.
    if (session.canJoinWaitlist(user)) {
        actions.push({ kind: 'join-waitlist', label: 'Join waitlist', hook: 'join-waitlist-button' });
    }

    if (session.canWatch(user)) {
        actions.push({ kind: 'watch', label: 'Spectate', hook: 'watch-button' });
    }

    if (session.canLeave(user)) {
        actions.push({
            kind: 'leave',
            label: session.isOnWaitlist(user) ? 'Leave waitlist' : 'Leave',
            hook: 'leave-button',
        });
    }

    if (session.canEditOrDelete(user)) {
        actions.push({ kind: 'edit', label: 'Edit', hook: 'update-button' });
        actions.push({ kind: 'delete', label: 'Delete', hook: 'delete-button' });
    }

    return actions;
}
