import {SocialMob} from './SocialMob';
import {IUser} from '../types';
import {SocialMobApi} from '../services/SocialMobApi';

describe('SocialMob', () => {
    let socialMob: SocialMob;
    const owner: IUser = {
        id: 0,
        avatar: "",
        email: "john@doe.test",
        name: "John Doe",
        github_nickname: "johnjohn"
    };

    beforeEach(() => {
        socialMob = new SocialMob({
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
        });
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
});
