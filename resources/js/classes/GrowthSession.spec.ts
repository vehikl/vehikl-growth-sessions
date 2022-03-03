import {GrowthSession} from './GrowthSession';
import {IGrowthSession, IUser} from '../types';
import {User} from './User';

describe('GrowthSession', () => {
    let growthSession: GrowthSession;
    const owner: IUser = {
        id: 0,
        avatar: "",
        name: "John Doe",
        github_nickname: "johnjohn",
        is_vehikl_member: true
    };

    const growthSessionJson: IGrowthSession = {
        id: 0,
        is_public: true,
        date: "2020-01-01",
        start_time: "03:00 pm",
        end_time: "05:00 pm",
        location: "Somewhere over the rainbow",
        discord_channel_id: null,
        anydesks_remote_desk_id: null,
        owner,
        attendees: [],
        comments: [],
        title: "The growth session title",
        topic: "The growth session topic",
        attendee_limit: null,
        anydesk: null,
    };

    beforeEach(() => {
        growthSession = new GrowthSession(growthSessionJson);
    });

    it('can return its dates in the proper google calendar style', () => {
        expect(growthSession.googleCalendarDate).toEqual('20200101T200000Z/20200101T220000Z');
    });

    describe('canJoin', () => {
        it('prevents joining when limit reached', () => {
            const growthSession: GrowthSession = new GrowthSession({...growthSessionJson, attendees: [], date: '2099-01-01', attendee_limit: 1 })
            growthSession.attendees.push(new User({id: 2, name: "John Doe", github_nickname: 'jdoe', is_vehikl_member: true, avatar: "http://example.com/jdoe"}));
            const someUser: IUser = {id: 3, name: "Jane Doe", is_vehikl_member:true, github_nickname: 'jdoe', avatar: "http://example.com/janedoe"}

            expect(growthSession.canJoin(someUser)).toBe(false);
        });

        it('allows joining when limit has not been reached', () => {
            const growthSession: GrowthSession = new GrowthSession({...growthSessionJson, attendees: [], date: '2099-01-01', attendee_limit: 2 })
            growthSession.attendees.push(new User({id: 2, name: "John Doe",  github_nickname: 'jdoe', avatar: "http://example.com/jdoe", is_vehikl_member: true}));
            const someUser: IUser = {id: 3, name: "Jane Doe", github_nickname: 'jdoe', is_vehikl_member: true, avatar: "http://example.com/janedoe"}

            expect(growthSession.canJoin(someUser)).toBe(true);
        });
    });
});
