import {SocialMob} from './SocialMob';
import {ISocialMob, IUser} from '../types';
import {SocialMobApi} from '../services/SocialMobApi';
import {User} from "./User";

describe('SocialMob', () => {
    let socialMob: SocialMob;
    const owner: IUser = {
        id: 0,
        avatar: "",
        email: "john@doe.test",
        name: "John Doe",
        github_nickname: "johnjohn"
    };

    const mobJson: ISocialMob = {
        attendees: [],
        comments: [],
        date: "2020-01-01",
        start_time: "03:00 pm",
        end_time: "05:00 pm",
        id: 0,
        location: "Somewhere over the rainbow",
        owner,
        title: "The mob title",
        topic: "The mob topic",
        attendee_limit: null
    };

    beforeEach(() => {
        socialMob = new SocialMob(mobJson);
    });

    it('can return its dates in the proper google calendar style', () => {
        expect(socialMob.googleCalendarDate).toEqual('20200101T200000Z/20200101T220000Z');
    });

    it('prompts the user to add the growth session to their calendar when they join', async () => {
        const userAgreedToAddToCalendar = true;
        window.confirm = jest.fn().mockReturnValue(userAgreedToAddToCalendar);
        window.open = jest.fn();
        SocialMobApi.join = jest.fn().mockImplementation(mob => mob);
        await socialMob.join();

        expect(window.open).toHaveBeenCalledWith(socialMob.calendarUrl, '_blank');
    });

    describe('canJoin', () => {
        it('prevents joining when limit reached', () => {
            const mob: SocialMob = new SocialMob({...mobJson, attendees: [], date: '2021-01-01', attendee_limit: 1 })
            mob.attendees.push(new User({id: 2, name: "John Doe", email: "j.doe@example.com", github_nickname: 'jdoe', avatar: "http://example.com/jdoe"}));
            const someUser: IUser = {id: 3, name: "Jane Doe", github_nickname: 'jdoe', email: "jane.doe@example.com", avatar: "http://example.com/janedoe"}

            expect(mob.canJoin(someUser)).toBe(false);
        });

        it('allows joining when limit has not been reached', () => {
            const mob: SocialMob = new SocialMob({...mobJson, attendees: [], date: '2021-01-01', attendee_limit: 2 })
            mob.attendees.push(new User({id: 2, name: "John Doe", email: "j.doe@example.com",  github_nickname: 'jdoe', avatar: "http://example.com/jdoe"}));
            const someUser: IUser = {id: 3, name: "Jane Doe", github_nickname: 'jdoe', email: "jane.doe@example.com", avatar: "http://example.com/janedoe"}

            expect(mob.canJoin(someUser)).toBe(true);
        });
    });
});
