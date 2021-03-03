import {GrowthSession} from './GrowthSession';
import {IGrowthSession, IUser} from '../types';
import {GrowthSessionApi} from '../services/GrowthSessionApi';
import {User} from './User';

describe('GrowthSession', () => {
    let growthSession: GrowthSession;
    const owner: IUser = {
        id: 0,
        avatar: "",
        email: "john@doe.test",
        name: "John Doe",
        github_nickname: "johnjohn"
    };

    const growthSessionJson: IGrowthSession = {
        attendees: [],
        comments: [],
        date: "2020-01-01",
        start_time: "03:00 pm",
        end_time: "05:00 pm",
        id: 0,
        location: "Somewhere over the rainbow",
        discord_channel_id: null,
        owner,
        title: "The growth session title",
        topic: "The growth session topic",
        attendee_limit: null
    };

    beforeEach(() => {
        growthSession = new GrowthSession(growthSessionJson);
    });

    it('can return its dates in the proper google calendar style', () => {
        expect(growthSession.googleCalendarDate).toEqual('20200101T200000Z/20200101T220000Z');
    });

    it('prompts the user to add the growth session to their calendar when they join', async () => {
        const userAgreedToAddToCalendar = true;
        window.confirm = jest.fn().mockReturnValue(userAgreedToAddToCalendar);
        window.open = jest.fn();
        GrowthSessionApi.join = jest.fn().mockImplementation(growthSession => growthSession);
        await growthSession.join();

        expect(window.open).toHaveBeenCalledWith(growthSession.calendarUrl, '_blank');
    });

    describe('canJoin', () => {
        it('prevents joining when limit reached', () => {
            const growthSession: GrowthSession = new GrowthSession({...growthSessionJson, attendees: [], date: '2099-01-01', attendee_limit: 1 })
            growthSession.attendees.push(new User({id: 2, name: "John Doe", email: "j.doe@example.com", github_nickname: 'jdoe', avatar: "http://example.com/jdoe"}));
            const someUser: IUser = {id: 3, name: "Jane Doe", github_nickname: 'jdoe', email: "jane.doe@example.com", avatar: "http://example.com/janedoe"}

            expect(growthSession.canJoin(someUser)).toBe(false);
        });

        it('allows joining when limit has not been reached', () => {
            const growthSession: GrowthSession = new GrowthSession({...growthSessionJson, attendees: [], date: '2099-01-01', attendee_limit: 2 })
            growthSession.attendees.push(new User({id: 2, name: "John Doe", email: "j.doe@example.com",  github_nickname: 'jdoe', avatar: "http://example.com/jdoe"}));
            const someUser: IUser = {id: 3, name: "Jane Doe", github_nickname: 'jdoe', email: "jane.doe@example.com", avatar: "http://example.com/janedoe"}

            expect(growthSession.canJoin(someUser)).toBe(true);
        });
    });
});
