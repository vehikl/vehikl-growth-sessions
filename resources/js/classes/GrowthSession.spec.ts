import { IGrowthSession, IUser } from '@/types';
import { GrowthSession } from './GrowthSession';
import { User } from './User';

describe('GrowthSession', () => {
    let growthSession: GrowthSession;
    const owner: IUser = {
        id: 0,
        avatar: '',
        name: 'John Doe',
        github_nickname: 'johnjohn',
        is_vehikl_member: true,
    };

    const growthSessionJson: IGrowthSession = {
        id: 0,
        is_public: true,
        allow_watchers: true,
        date: '2020-01-01',
        start_time: '03:00 pm',
        end_time: '05:00 pm',
        location: 'Somewhere over the rainbow',
        location_segments: [{ type: 'text', value: 'Somewhere over the rainbow' }],
        discord_channel_id: null,
        owner,
        attendees: [],
        watchers: [],
        comments: [],
        title: 'The growth session title',
        topic: 'The growth session topic',
        topic_segments: [{ type: 'text', value: 'The growth session topic' }],
        attendee_limit: null,
        anydesk: null,
        tags: [],
    };

    beforeEach(() => {
        growthSession = new GrowthSession(growthSessionJson);
    });

    it('can return its dates in the proper google calendar style', () => {
        expect(growthSession.googleCalendarDate).toEqual('20200101T200000Z/20200101T220000Z');
    });

    describe('shareUrl', () => {
        const originalUrl = window.location.href;

        afterEach(() => {
            window.history.replaceState({}, '', originalUrl);
        });

        it('points at the board week that holds the session, with its detail open', () => {
            window.history.replaceState({}, '', '/board');

            const url = new URL(new GrowthSession({ ...growthSessionJson, id: 42 }).shareUrl);

            expect(url.origin).toEqual(window.location.origin);
            expect(url.pathname).toEqual('/board');
            expect(url.searchParams.get('date')).toEqual('2020-01-01');
            expect(url.searchParams.get('session')).toEqual('42');
        });

        it('drops whatever else the current address bar is carrying', () => {
            window.history.replaceState({}, '', '/board?date=2021-05-05&session=7&view=week');

            const url = new URL(new GrowthSession({ ...growthSessionJson, id: 42 }).shareUrl);

            expect(url.searchParams.get('date')).toEqual('2020-01-01');
            expect(url.searchParams.get('session')).toEqual('42');
            expect(url.searchParams.has('view')).toBe(false);
        });
    });

    describe('canJoin', () => {
        it('prevents joining when limit reached', () => {
            const growthSession: GrowthSession = new GrowthSession({ ...growthSessionJson, attendees: [], date: '2099-01-01', attendee_limit: 1 });
            growthSession.attendees.push(
                new User({ id: 2, name: 'John Doe', github_nickname: 'jdoe', is_vehikl_member: true, avatar: 'http://example.com/jdoe' }),
            );
            const someUser: IUser = {
                id: 3,
                name: 'Jane Doe',
                is_vehikl_member: true,
                github_nickname: 'jdoe',
                avatar: 'http://example.com/janedoe',
            };

            expect(growthSession.canJoin(someUser)).toBe(false);
        });

        it('allows joining when limit has not been reached', () => {
            const growthSession: GrowthSession = new GrowthSession({ ...growthSessionJson, attendees: [], date: '2099-01-01', attendee_limit: 2 });
            growthSession.attendees.push(
                new User({ id: 2, name: 'John Doe', github_nickname: 'jdoe', avatar: 'http://example.com/jdoe', is_vehikl_member: true }),
            );
            const someUser: IUser = {
                id: 3,
                name: 'Jane Doe',
                github_nickname: 'jdoe',
                is_vehikl_member: true,
                avatar: 'http://example.com/janedoe',
            };

            expect(growthSession.canJoin(someUser)).toBe(true);
        });
    });

    describe('hasReachedAttendeeLimit', () => {
        function sessionWith(attendeeCount: number, attendee_limit: number | null): GrowthSession {
            const attendees = Array.from({ length: attendeeCount }, (_, index) => ({
                id: index + 1,
                name: `Attendee ${index + 1}`,
                github_nickname: `attendee${index + 1}`,
                is_vehikl_member: true,
                avatar: '',
            }));

            return new GrowthSession({ ...growthSessionJson, attendee_limit, attendees });
        }

        it('is false below the limit', () => {
            expect(sessionWith(3, 4).hasReachedAttendeeLimit()).toBe(false);
        });

        it('is true at the limit', () => {
            expect(sessionWith(4, 4).hasReachedAttendeeLimit()).toBe(true);
        });

        it('is true past the limit', () => {
            expect(sessionWith(5, 4).hasReachedAttendeeLimit()).toBe(true);
        });

        it('is false for a limitless session no matter how many have joined', () => {
            expect(sessionWith(50, null).hasReachedAttendeeLimit()).toBe(false);
        });

        it('is false for an empty session', () => {
            expect(sessionWith(0, 4).hasReachedAttendeeLimit()).toBe(false);
        });
    });
});
