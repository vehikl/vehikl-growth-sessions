import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import growthSessionsThisWeekJson from '../../../tests/fixtures/WeekSocials.json';
import growthSessionWithComments from '../../../tests/fixtures/GrowthSessionWithComments.json';
import {GrowthSessionApi} from './GrowthSessionApi';
import {IGrowthSession, IUpdateGrowthSessionRequest} from '../types';
import {DateTime} from '../classes/DateTime';
import {WeekGrowthSessions} from '../classes/WeekGrowthSessions';

const growthSessionsThisWeek: WeekGrowthSessions = new WeekGrowthSessions(growthSessionsThisWeekJson);
const dummyGrowthSession: IGrowthSession = growthSessionWithComments;

describe('GrowthSessionApi', () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
    });

    afterEach(() => {
        mockBackend.restore();
    });

    it('returns the growth sessions of the current week if no date is provided', async () => {
        DateTime.setTestNow('2020-05-01');
        mockBackend.onGet('social_mobs/week?date=2020-05-01').reply(200, growthSessionsThisWeekJson);

        const result = await GrowthSessionApi.getAllMobsOfTheWeek();

        expect(result).toEqual(growthSessionsThisWeek);
    });


    it('returns the growth sessions of the week of the date provided', async() => {
        DateTime.setTestNow('2020-05-01');
        mockBackend.onGet('social_mobs/week?date=2020-01-01').reply(200, growthSessionsThisWeekJson);

        const result = await GrowthSessionApi.getAllMobsOfTheWeek('2020-01-01');

        expect(result).toEqual(growthSessionsThisWeek);
    });

    it('stores a new growth session', async () => {
        mockBackend.onPost('social_mobs').reply(201, growthSessionWithComments);

        const result = await GrowthSessionApi.store(growthSessionWithComments);

        expect(result.topic).toEqual(growthSessionWithComments.topic);
    });

    it('updates an existing growth session', async () => {
        let newTopic: string = 'A totally new topic';
        mockBackend.onPut(`social_mobs/${dummyGrowthSession.id}`).reply(200, {
            ...dummyGrowthSession,
            topic: newTopic
        });

        const payload: IUpdateGrowthSessionRequest = {topic: newTopic};
        const result = await GrowthSessionApi.update(dummyGrowthSession, payload);

        expect(result.topic).toEqual(newTopic);
    });

    it('deletes an existing growth session', async () => {
        mockBackend.onDelete(`social_mobs/${dummyGrowthSession.id}`).reply(200);

        const result = await GrowthSessionApi.delete(dummyGrowthSession);

        expect(result).toBe(true);
    });

    it('joins an existing growth session', async () => {
        mockBackend.onPost(`social_mobs/${dummyGrowthSession.id}/join`).reply(200, dummyGrowthSession);

        await GrowthSessionApi.join(dummyGrowthSession);

        expect(mockBackend.history.post[0].url).toBe(`/social_mobs/${dummyGrowthSession.id}/join`);
    });

    it('leaves an existing growth session', async () => {
        mockBackend.onPost(`social_mobs/${dummyGrowthSession.id}/leave`).reply(200, dummyGrowthSession);

        await GrowthSessionApi.leave(dummyGrowthSession);

        expect(mockBackend.history.post[0].url).toBe(`/social_mobs/${dummyGrowthSession.id}/leave`);
    });

    it('allows a new comment to be created', async () => {
        mockBackend.onPost(`social_mobs/${dummyGrowthSession.id}/comments`).reply(201, dummyGrowthSession);
        const content = 'Hello world';

        await GrowthSessionApi.postComment(dummyGrowthSession, content);

        expect(mockBackend.history.post[0].url).toBe(`/social_mobs/${dummyGrowthSession.id}/comments`);
        expect(JSON.parse(mockBackend.history.post[0].data)).toEqual({content: content})
    });

    it('allows a comment to be deleted', async () => {
        mockBackend.onDelete(`social_mobs/${dummyGrowthSession.id}/comments/${dummyGrowthSession.comments[0].id}`).reply(200, dummyGrowthSession);

        await GrowthSessionApi.deleteComment(dummyGrowthSession.comments[0]);

        expect(mockBackend.history.delete[0].url).toBe(`/social_mobs/${dummyGrowthSession.id}/comments/${dummyGrowthSession.comments[0].id}`);
    });
});
