import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import socialsThisWeekJson from '../../../tests/fixtures/WeekSocials.json';
import {SocialMobApi} from './SocialMobApi';
import {ISocialMob, IUpdateSocialMobRequest} from '../types';
import {DateTime} from '../classes/DateTime';
import {WeekMobs} from '../classes/WeekMobs';

const socialsThisWeek: WeekMobs = new WeekMobs(socialsThisWeekJson);
const dummyMob: ISocialMob = {
    id: 1,
    attendees: [],
    comments: [],
    topic: 'Start Topic',
    owner: {avatar: '', name: 'foobar', email: 'any@thing.com', id: 1},
    location: 'Slack #social-mobbing',
    date: '2020-05-31',
    start_time: '03:30 pm',
    end_time: '05:00 pm',
};

describe('SocialMobApi', () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
    });

    afterEach(() => {
        mockBackend.restore();
    });

    it('returns the mobs of the current week if no date is provided', async () => {
        DateTime.setTestNow('2020-05-01');
        mockBackend.onGet('social_mobs/week?date=2020-05-01').reply(200, socialsThisWeekJson);

        const result = await SocialMobApi.getAllMobsOfTheWeek();

        expect(result).toEqual(socialsThisWeek);
    });


    it('returns the mobs of the week of the date provided', async() => {
        DateTime.setTestNow('2020-05-01');
        mockBackend.onGet('social_mobs/week?date=2020-01-01').reply(200, socialsThisWeekJson);

        const result = await SocialMobApi.getAllMobsOfTheWeek('2020-01-01');

        expect(result).toEqual(socialsThisWeek);
    });

    it('stores a new mob', async () => {
        const storeRequest = {
            topic: 'Any topic',
            location: 'Slack #social-mobbing',
            date: '2020-05-31',
            start_time: '20:30:20'
        };
        mockBackend.onPost('social_mobs').reply(201, {
            id: 1,
            ...storeRequest
        });

        const result = await SocialMobApi.store(storeRequest);

        expect(result.topic).toEqual(storeRequest.topic);
    });

    it('updates an existing mob', async () => {
        let newTopic: string = 'A totally new topic';
        mockBackend.onPut(`social_mobs/${dummyMob.id}`).reply(200, {
            ...dummyMob,
            topic: newTopic
        });

        const payload: IUpdateSocialMobRequest = {topic: newTopic};
        const result = await SocialMobApi.update(dummyMob, payload);

        expect(result.topic).toEqual(newTopic);
    });

    it('deletes an existing mob', async () => {
        mockBackend.onDelete(`social_mobs/${dummyMob.id}`).reply(200);

        const result = await SocialMobApi.delete(dummyMob);

        expect(result).toBe(true);
    });

    it('joins an existing mob', async () => {
        mockBackend.onPost(`social_mobs/${dummyMob.id}/join`).reply(200, dummyMob);

        await SocialMobApi.join(dummyMob);

        expect(mockBackend.history.post[0].url).toBe(`/social_mobs/${dummyMob.id}/join`);
    });

    it('leaves an existing mob', async () => {
        mockBackend.onPost(`social_mobs/${dummyMob.id}/leave`).reply(200, dummyMob);

        await SocialMobApi.leave(dummyMob);

        expect(mockBackend.history.post[0].url).toBe(`/social_mobs/${dummyMob.id}/leave`);
    });

    it('allows a new comment to be created', async () => {
        mockBackend.onPost(`social_mobs/${dummyMob.id}/comments`).reply(201, dummyMob);
        const content = 'Hello world';

        await SocialMobApi.comment(dummyMob, content);

        expect(mockBackend.history.post[0].url).toBe(`/social_mobs/${dummyMob.id}/comments`);
        expect(JSON.parse(mockBackend.history.post[0].data)).toEqual({content: content})
    });
});
