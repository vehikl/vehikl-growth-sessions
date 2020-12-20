import BaseApi from './BaseApi';
import {IComment, IGrowthSession, IStoreSocialMobRequest, IUpdateSocialMobRequest, IWeekMobs} from '../types';
import {DateTime} from '../classes/DateTime';
import {GrowthSession} from '../classes/GrowthSession';
import {WeekMobs} from '../classes/WeekMobs';

export class SocialMobApi extends BaseApi {
    static async getAllMobsOfTheWeek(date: string = DateTime.today().toDateString()): Promise<WeekMobs> {
        let dateString = DateTime.parseByDate(date).toDateString();
        let response = await BaseApi.httpRequest.get<IWeekMobs>(`/social_mobs/week?date=${dateString}`);
        return new WeekMobs(response.data);
    }

    static async store(payload: IStoreSocialMobRequest): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post<IGrowthSession>('/social_mobs', payload);
        return new GrowthSession(response.data);
    }

    static async update(target: IGrowthSession, payload: IUpdateSocialMobRequest): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.put<IGrowthSession>(`/social_mobs/${target.id}`, payload);
        return new GrowthSession(response.data);
    }

    static async delete(target: IGrowthSession): Promise<boolean> {
        await BaseApi.httpRequest.delete(`/social_mobs/${target.id}`);
        return true;
    }

    static async join(target: IGrowthSession): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post(`/social_mobs/${target.id}/join`);
        return new GrowthSession(response.data);
    }

    static async leave(target: IGrowthSession): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post(`/social_mobs/${target.id}/leave`);
        return new GrowthSession(response.data);
    }

    static async postComment(target: IGrowthSession, content: string): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.post(`/social_mobs/${target.id}/comments`, {content});
        return new GrowthSession(response.data);
    }

    static async deleteComment(comment: IComment): Promise<GrowthSession> {
        let response = await BaseApi.httpRequest.delete(`/social_mobs/${comment.social_mob_id}/comments/${comment.id}`);
        return new GrowthSession(response.data);
    }

    static editUrl(target: IGrowthSession): string {
        return `/social_mobs/${target.id}/edit`
    }

    static showUrl(target: IGrowthSession): string {
        return `/social_mobs/${target.id}`;
    }
}
