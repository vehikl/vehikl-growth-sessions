import { GrowthSession } from '@/classes/GrowthSession';
import { IUser } from '@/types';

export type VisibilityFilter = 'all' | 'public' | 'private';

export interface SessionFilterCriteria {
    /** Absent for guests, which forces public-only regardless of `visibility`. */
    user?: IUser;
    tagIds: number[];
    visibility: VisibilityFilter;
    /** Already debounced by the caller. */
    searchQuery: string;
}

function matchesTags(session: GrowthSession, tagIds: number[]): boolean {
    if (tagIds.length === 0) return true;

    return session.tags.some((tag) => tagIds.includes(tag.id));
}

function matchesVisibility(session: GrowthSession, criteria: SessionFilterCriteria): boolean {
    // Guests can only ever see public sessions. The server enforces this too, but
    // guarding here means private sessions can't linger in the UI after logging out
    // (before any refetch resolves), and the Vehikl-only visibility filter below
    // simply doesn't apply to guests.
    if (!criteria.user) {
        return session.is_public;
    }

    if (criteria.visibility === 'private') {
        return !session.is_public;
    }

    if (criteria.visibility === 'public') {
        return session.is_public;
    }

    return true;
}

function matchesSearch(session: GrowthSession, searchQuery: string): boolean {
    if (!searchQuery) return true;

    const query = searchQuery.toLowerCase();

    return (
        session.title.toLowerCase().includes(query) ||
        session.topic.toLowerCase().includes(query) ||
        session.owner.name.toLowerCase().includes(query) ||
        session.attendees.some((attendee) => attendee.name.toLowerCase().includes(query))
    );
}

/** Narrows a day's growth sessions down to the ones the current filters allow. */
export function filterSessions(sessions: GrowthSession[], criteria: SessionFilterCriteria): GrowthSession[] {
    return sessions.filter(
        (session) => matchesTags(session, criteria.tagIds) && matchesVisibility(session, criteria) && matchesSearch(session, criteria.searchQuery),
    );
}
