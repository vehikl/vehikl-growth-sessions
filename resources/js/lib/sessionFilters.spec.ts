import { GrowthSession } from '@/classes/GrowthSession';
import { filterSessions, type SessionFilterCriteria } from '@/lib/sessionFilters';
import { IUser } from '@/types';
import { describe, expect, it } from 'vitest';

const vehiklUser: IUser = {
    avatar: 'lastAirBender.jpg',
    github_nickname: 'jackjack',
    id: 987,
    name: 'Jack Bauer',
    is_vehikl_member: true,
};

function makeSession(overrides: Partial<Record<string, unknown>> = {}): GrowthSession {
    return new GrowthSession({
        id: 1,
        title: 'Refactoring Kata',
        topic: 'Improving legacy code',
        date: '2020-01-15',
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
        tags: [],
        ...overrides,
    } as never);
}

function criteria(overrides: Partial<SessionFilterCriteria> = {}): SessionFilterCriteria {
    return {
        user: vehiklUser,
        tagIds: [],
        visibility: 'all',
        searchQuery: '',
        ...overrides,
    };
}

function titlesOf(sessions: GrowthSession[]): string[] {
    return sessions.map((session) => session.title);
}

describe('filterSessions', () => {
    describe('tags', () => {
        it('keeps every session when no tags are selected', () => {
            const sessions = [makeSession({ title: 'A' }), makeSession({ title: 'B' })];

            expect(titlesOf(filterSessions(sessions, criteria({ tagIds: [] })))).toEqual(['A', 'B']);
        });

        it('keeps sessions carrying any of the selected tags', () => {
            const sessions = [
                makeSession({ title: 'Has tag 1', tags: [{ id: 1, name: 'foo' }] }),
                makeSession({ title: 'Has tag 2', tags: [{ id: 2, name: 'bar' }] }),
                makeSession({ title: 'Has tag 3', tags: [{ id: 3, name: 'baz' }] }),
                makeSession({ title: 'Untagged', tags: [] }),
            ];

            expect(titlesOf(filterSessions(sessions, criteria({ tagIds: [1, 2] })))).toEqual(['Has tag 1', 'Has tag 2']);
        });
    });

    describe('visibility', () => {
        const sessions = () => [makeSession({ title: 'Public', is_public: true }), makeSession({ title: 'Private', is_public: false })];

        it('keeps both when set to all', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ visibility: 'all' })))).toEqual(['Public', 'Private']);
        });

        it('keeps only public sessions when set to public', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ visibility: 'public' })))).toEqual(['Public']);
        });

        it('keeps only private sessions when set to private', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ visibility: 'private' })))).toEqual(['Private']);
        });

        it('hides private sessions from guests whatever the visibility filter says', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ user: undefined, visibility: 'private' })))).toEqual(['Public']);
            expect(titlesOf(filterSessions(sessions(), criteria({ user: undefined, visibility: 'all' })))).toEqual(['Public']);
        });
    });

    describe('search', () => {
        const sessions = () => [
            makeSession({ title: 'Voluptas vel distinctio', topic: 'Testing patterns' }),
            makeSession({ title: 'Pairing basics', topic: 'How to mob effectively' }),
            makeSession({
                title: 'Owned by someone else',
                topic: 'Unrelated',
                owner: { id: 2, name: 'Grace Hopper', avatar: '', github_nickname: 'grace', is_vehikl_member: true },
            }),
            makeSession({
                title: 'Has attendees',
                topic: 'Unrelated',
                attendees: [{ id: 3, name: 'Alan Turing', avatar: '', github_nickname: 'alan', is_vehikl_member: true }],
            }),
        ];

        it('keeps every session when the query is empty', () => {
            expect(filterSessions(sessions(), criteria({ searchQuery: '' }))).toHaveLength(4);
        });

        it('matches on title', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ searchQuery: 'voluptas' })))).toEqual(['Voluptas vel distinctio']);
        });

        it('matches on topic', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ searchQuery: 'mob' })))).toEqual(['Pairing basics']);
        });

        it('matches on owner name', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ searchQuery: 'hopper' })))).toEqual(['Owned by someone else']);
        });

        it('matches on attendee name', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ searchQuery: 'turing' })))).toEqual(['Has attendees']);
        });

        it('is case insensitive', () => {
            expect(titlesOf(filterSessions(sessions(), criteria({ searchQuery: 'VOLUPTAS' })))).toEqual(['Voluptas vel distinctio']);
        });
    });

    it('applies tag, visibility and search together', () => {
        const sessions = [
            makeSession({ title: 'Match', is_public: false, tags: [{ id: 1, name: 'foo' }] }),
            makeSession({ title: 'Wrong visibility', is_public: true, tags: [{ id: 1, name: 'foo' }] }),
            makeSession({ title: 'Wrong tag', is_public: false, tags: [{ id: 2, name: 'bar' }] }),
            makeSession({ title: 'Unsearchable', is_public: false, topic: 'nope', tags: [{ id: 1, name: 'foo' }] }),
        ];

        const result = filterSessions(sessions, criteria({ tagIds: [1], visibility: 'private', searchQuery: 'match' }));

        expect(titlesOf(result)).toEqual(['Match']);
    });
});
