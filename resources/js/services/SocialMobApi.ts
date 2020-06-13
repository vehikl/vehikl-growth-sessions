import BaseApi from './BaseApi';
import {ISocialMob, IStoreSocialMobRequest, IUpdateSocialMobRequest, IWeekMobs} from '../types';
import {DateTime} from '../classes/DateTime';
import {SocialMob} from '../classes/SocialMob';
import {WeekMobs} from '../classes/WeekMobs';

export class SocialMobApi extends BaseApi {
    static async getAllMobsOfTheWeek(date: string = DateTime.today().toDateString()): Promise<WeekMobs> {
        let dateString = DateTime.parseByDate(date).toDateString();
        let response = await BaseApi.httpRequest.get<IWeekMobs>(`/social_mobs/week?date=${dateString}`);
        return new WeekMobs(response.data);
    }

    static async store(payload: IStoreSocialMobRequest): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.post<ISocialMob>('/social_mobs', payload);
        return new SocialMob(response.data);
    }

    static async update(target: ISocialMob, payload: IUpdateSocialMobRequest): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.put<ISocialMob>(`/social_mobs/${target.id}`, payload);
        return new SocialMob(response.data);
    }

    static async delete(target: ISocialMob): Promise<boolean> {
        await BaseApi.httpRequest.delete(`/social_mobs/${target.id}`);
        return true;
    }

    static async join(target: ISocialMob): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.post(`/social_mobs/${target.id}/join`);
        return new SocialMob(response.data);
    }

    static async leave(target: ISocialMob): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.post(`/social_mobs/${target.id}/leave`);
        return new SocialMob(response.data);
    }

    static async comment(target: ISocialMob, content: string): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.post(`/social_mobs/${target.id}/comments`, {content});
        return new SocialMob(response.data);
    }

    static editUrl(target: ISocialMob): string {
        return `/social_mobs/${target.id}/edit`
    }

    static showUrl(target: ISocialMob): string {
        return `/social_mobs/${target.id}`;
    }
}
