import { GrowthSession } from '@/classes/GrowthSession';
import { avatarColor, capacityLabel, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { afterEach, describe, expect, it, vi } from 'vitest';

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
