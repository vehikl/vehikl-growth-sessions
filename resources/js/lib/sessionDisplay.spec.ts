import { GrowthSession } from '@/classes/GrowthSession';
import { avatarColor, capacityLabel, sessionActions, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { IUser } from '@/types';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

function makeSession(overrides: Partial<Record<string, unknown>> = {}): GrowthSession {
    return new GrowthSession({
        id: 1,
        title: 'Refactoring Kata',
        topic: 'Improving legacy code',
        date: '2024-06-15',
        start_time: '03:30 pm',
        end_time: '05:00 pm',
        location: 'Zoom',
        is_public: true,
        allow_watchers: true,
        attendee_limit: 10,
        owner: { id: 1, name: 'Ada Lovelace', avatar: '', github_nickname: 'ada', is_vehikl_member: true },
        attendees: [],
        watchers: [],
        comments: [],
        discord_channel_id: null,
        anydesk: null,
        tags: [],
        ...overrides,
    } as never);
}

describe('avatarColor', () => {
    const palette = ['#e0632a', '#3f6fb5', '#3fb56d', '#b53f8f', '#b58f3f', '#3fb5a9'];

    it('always returns a colour from the palette', () => {
        for (const name of ['Ada Lovelace', 'Grace Hopper', 'Alan Turing', 'x', 'a very long name indeed']) {
            expect(palette).toContain(avatarColor(name));
        }
    });

    it('is deterministic for the same name', () => {
        expect(avatarColor('Ada Lovelace')).toBe(avatarColor('Ada Lovelace'));
    });

    it('maps the empty string to the first palette entry', () => {
        expect(avatarColor('')).toBe('#e0632a');
    });
});

describe('sessionStatus', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    function freezeAt(year: number, monthIndex: number, day: number, hour: number, minute = 0) {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(year, monthIndex, day, hour, minute, 0));
    }

    it('is live when now is within the session window', () => {
        freezeAt(2024, 5, 15, 12); // noon
        const session = makeSession({ date: '2024-06-15', start_time: '11:00 am', end_time: '01:00 pm' });
        expect(sessionStatus(session)).toBe('live');
    });

    it('is upcoming when the session has not started yet', () => {
        freezeAt(2024, 5, 15, 12);
        const session = makeSession({ date: '2024-06-15', start_time: '02:00 pm', end_time: '03:00 pm' });
        expect(sessionStatus(session)).toBe('upcoming');
    });

    it('is finished once the end time has passed earlier the same day', () => {
        freezeAt(2024, 5, 15, 12);
        const session = makeSession({ date: '2024-06-15', start_time: '09:00 am', end_time: '10:00 am' });
        expect(sessionStatus(session)).toBe('finished');
    });

    it('is finished for a session on a previous day', () => {
        freezeAt(2024, 5, 15, 12);
        const session = makeSession({ date: '2024-06-14', start_time: '11:00 am', end_time: '01:00 pm' });
        expect(sessionStatus(session)).toBe('finished');
    });

    it('falls back to hasAlreadyHappened when the times cannot be parsed', () => {
        const finished = { date: '', start_time: '', end_time: '', hasAlreadyHappened: true } as unknown as GrowthSession;
        const upcoming = { date: '', start_time: '', end_time: '', hasAlreadyHappened: false } as unknown as GrowthSession;

        expect(sessionStatus(finished)).toBe('finished');
        expect(sessionStatus(upcoming)).toBe('upcoming');
    });
});

describe('statusMeta', () => {
    it('describes each status with a label and colour', () => {
        expect(statusMeta('live')).toEqual({ color: 'var(--color-gs-live)', label: 'LIVE' });
        expect(statusMeta('upcoming')).toEqual({ color: 'var(--color-gs-upcoming)', label: 'UPCOMING' });
        expect(statusMeta('finished')).toEqual({ color: 'var(--color-gs-finished)', label: 'FINISHED' });
    });
});

describe('capacityLabel', () => {
    it('shows joined over limit for a capped session', () => {
        const session = makeSession({
            attendee_limit: 4,
            attendees: [
                { id: 2, name: 'B', avatar: '', github_nickname: 'b', is_vehikl_member: true },
                { id: 3, name: 'C', avatar: '', github_nickname: 'c', is_vehikl_member: true },
            ],
        });
        expect(capacityLabel(session)).toBe('2/4');
    });

    it('shows only the count for a limitless session', () => {
        const session = makeSession({
            attendee_limit: null,
            attendees: [{ id: 2, name: 'B', avatar: '', github_nickname: 'b', is_vehikl_member: true }],
        });
        expect(capacityLabel(session)).toBe('1');
    });
});

describe('sessionActions', () => {
    // The shared session sits on a fixed date, so the clock is pinned beside it - otherwise every
    // list here empties out the moment that date falls into the past.
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(2024, 5, 15, 12, 0, 0));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    const owner: IUser = { id: 1, name: 'Ada Lovelace', avatar: '', github_nickname: 'ada', is_vehikl_member: true };
    const member: IUser = { id: 2, name: 'Grace Hopper', avatar: '', github_nickname: 'grace', is_vehikl_member: true };
    const other: IUser = { id: 3, name: 'Alan Turing', avatar: '', github_nickname: 'alan', is_vehikl_member: true };

    /** The board renders whatever comes back, in order - so the kinds are the whole contract. */
    function kindsFor(overrides: Partial<Record<string, unknown>>, user?: IUser | null): string[] {
        return sessionActions(makeSession(overrides), user).map((action) => action.kind);
    }

    const openSession = { attendee_limit: 4, attendees: [] };
    const fullSession = { attendee_limit: 1, attendees: [other] };

    it('offers nothing to somebody not logged in', () => {
        expect(kindsFor(openSession, null)).toEqual([]);
    });

    it('offers a seat and a spectator place while seats are left', () => {
        expect(kindsFor(openSession, member)).toEqual(['join', 'watch']);
    });

    it('offers the queue instead of a seat once the seats are gone', () => {
        expect(kindsFor(fullSession, member)).toEqual(['join-waitlist', 'watch']);
    });

    it('never offers a seat and the queue together', () => {
        for (const session of [openSession, fullSession]) {
            const kinds = kindsFor(session, member);

            expect(kinds.includes('join') && kinds.includes('join-waitlist')).toBe(false);
        }
    });

    it('offers a queued member their way out and nothing else', () => {
        expect(kindsFor({ ...fullSession, waitlist: [member] }, member)).toEqual(['leave']);
    });

    it('names the way out after the place being given up', () => {
        const queued = sessionActions(makeSession({ ...fullSession, waitlist: [member] }), member);
        const seated = sessionActions(makeSession({ attendee_limit: 4, attendees: [member] }), member);

        expect(queued.find((action) => action.kind === 'leave')?.label).toBe('Leave waitlist');
        expect(seated.find((action) => action.kind === 'leave')?.label).toBe('Leave');
    });

    it('offers an attendee their way out rather than a second role', () => {
        expect(kindsFor({ attendee_limit: 4, attendees: [member] }, member)).toEqual(['leave']);
    });

    it('offers the owner the session itself, and no way to join their own', () => {
        expect(kindsFor(openSession, owner)).toEqual(['edit', 'delete']);
    });

    it('offers nothing to act on once the session has finished', () => {
        expect(kindsFor({ ...fullSession, date: '2000-01-01' }, member)).toEqual([]);
    });

    it('offers no spectator place where the session refuses watchers', () => {
        expect(kindsFor({ ...openSession, allow_watchers: false }, member)).toEqual(['join']);
    });
});
