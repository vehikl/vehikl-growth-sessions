import { IGrowthSession, IUser } from '@/types';
import moment from 'moment-timezone';
import { GrowthSession } from './GrowthSession';
import { User } from './User';

function viewingFrom(zone: string) {
    vi.spyOn(moment.tz, 'guess').mockReturnValue(zone);
}

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
        is_unlisted: false,
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
        waitlist: [],
        waitlist_position: null,
        comments: [],
        title: 'The growth session title',
        topic: 'The growth session topic',
        topic_segments: [{ type: 'text', value: 'The growth session topic' }],
        attendee_limit: null,
        anydesk: null,
        tags: [],
        series_name: null,
    };

    beforeEach(() => {
        growthSession = new GrowthSession(growthSessionJson);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('can return its dates in the proper google calendar style', () => {
        expect(growthSession.googleCalendarDate).toEqual('20200101T200000Z/20200101T220000Z');
    });

    it('keeps the google calendar link on the scheduled instant for a viewer in another zone', () => {
        viewingFrom('Asia/Tokyo');

        expect(new GrowthSession(growthSessionJson).googleCalendarDate).toEqual('20200101T200000Z/20200101T220000Z');
    });

    describe('the session window', () => {
        it('is shown as-is to a viewer in the zone the session was scheduled in', () => {
            viewingFrom('America/Toronto');

            const session = new GrowthSession(growthSessionJson);

            expect(session.startTime).toEqual('03:00');
            expect(session.endTime).toEqual('05:00 pm');
            expect(session.timeRange).toEqual('03:00 – 05:00 pm');
        });

        it('is moved onto the clock of a viewer elsewhere', () => {
            viewingFrom('America/Vancouver');

            const session = new GrowthSession(growthSessionJson);

            expect(session.startTime).toEqual('12:00');
            expect(session.endTime).toEqual('02:00 pm');
            expect(session.timeRange).toEqual('12:00 – 02:00 pm');
        });

        it('warns a viewer far enough ahead that the session lands on their next day', () => {
            viewingFrom('Asia/Tokyo');

            const session = new GrowthSession(growthSessionJson);

            expect(session.viewerDayNote).toEqual('next day');
            expect(session.timeRange).toEqual('05:00 – 07:00 am (next day)');
        });

        it('says nothing about the day when the viewer reads it on the same date', () => {
            viewingFrom('America/Vancouver');

            expect(new GrowthSession(growthSessionJson).viewerDayNote).toEqual('');
        });

        it('falls back to the stored reading when a time cannot be parsed', () => {
            const session = new GrowthSession({ ...growthSessionJson, start_time: 'whenever', end_time: '' });

            expect(session.startTime).toEqual('whenever');
            expect(session.endTime).toEqual('');
            expect(session.hasAlreadyHappened).toBe(false);
        });
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

    describe('the waitlist', () => {
        const hopeful: IUser = { id: 3, name: 'Jane Doe', github_nickname: 'jane', is_vehikl_member: true, avatar: '' };
        const seated: IUser = { id: 2, name: 'John Doe', github_nickname: 'john', is_vehikl_member: true, avatar: '' };
        const inLine: IUser = { id: 4, name: 'Ada Byron', github_nickname: 'ada', is_vehikl_member: true, avatar: '' };

        function fullSession(overrides: Partial<IGrowthSession> = {}): GrowthSession {
            return new GrowthSession({ ...growthSessionJson, date: '2099-01-01', attendee_limit: 1, attendees: [seated], ...overrides });
        }

        it('is offered once the seats are gone', () => {
            expect(fullSession().canJoinWaitlist(hopeful)).toBe(true);
        });

        it('is not offered while a seat remains, where joining outright is', () => {
            const session = fullSession({ attendee_limit: 2 });

            expect(session.canJoinWaitlist(hopeful)).toBe(false);
            expect(session.canJoin(hopeful)).toBe(true);
        });

        it('is never offered alongside joining', () => {
            const session = fullSession();

            expect(session.canJoin(hopeful)).toBe(false);
            expect(session.canJoinWaitlist(hopeful)).toBe(true);
        });

        it('is not offered to the owner, to someone already taking part, or to anyone waiting already', () => {
            expect(fullSession().canJoinWaitlist(owner)).toBe(false);
            expect(fullSession().canJoinWaitlist(seated)).toBe(false);
            expect(fullSession({ waitlist: [inLine] }).canJoinWaitlist(inLine)).toBe(false);
        });

        it('is not offered on a session that has already happened, nor to a visitor who is not signed in', () => {
            expect(fullSession({ date: '2020-01-01' }).canJoinWaitlist(hopeful)).toBe(false);
            expect(fullSession().canJoinWaitlist(null)).toBe(false);
        });

        it('is not offered on a limitless session, which is never full', () => {
            expect(fullSession({ attendee_limit: null }).canJoinWaitlist(hopeful)).toBe(false);
        });

        it('knows who is waiting on it', () => {
            const session = fullSession({ waitlist: [inLine] });

            expect(session.isOnWaitlist(inLine)).toBe(true);
            expect(session.isOnWaitlist(hopeful)).toBe(false);
            expect(session.isOnWaitlist(null)).toBe(false);
        });

        it('holds someone waiting to that one role: spectating means leaving the queue first', () => {
            expect(fullSession({ waitlist: [inLine] }).canWatch(inLine)).toBe(false);
            expect(fullSession().canWatch(hopeful)).toBe(true);
        });

        it('lets someone waiting withdraw, the same way an attendee leaves', () => {
            expect(fullSession({ waitlist: [inLine] }).canLeave(inLine)).toBe(true);
            expect(fullSession().canLeave(hopeful)).toBe(false);
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
