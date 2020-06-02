import BaseApi from './BaseApi';
import {ISocialMob, IStoreSocialMobRequest, IUpdateSocialMobRequest, IWeekMobs} from '../types';
import {DateTimeApi} from './DateTimeApi';
import {SocialMob} from '../classes/SocialMob';
import {WeekMobs} from '../classes/WeekMobs';

export class SocialMobApi extends BaseApi {
    static async getAllMobsOfTheWeek(date: string = DateTimeApi.today().toDateString()): Promise<WeekMobs> {
        let dateString = DateTimeApi.parseByDate(date).toDateString();
        let response = await BaseApi.httpRequest.get<IWeekMobs>(`/social_mob/week?date=${dateString}`);
        return new WeekMobs(response.data);
    }

    static async store(payload: IStoreSocialMobRequest): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.post<ISocialMob>('/social_mob', payload);
        return new SocialMob(response.data);
    }

    static async update(target: ISocialMob, payload: IUpdateSocialMobRequest): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.put<ISocialMob>(`/social_mob/${target.id}`, payload);
        return new SocialMob(response.data);
    }

    static async delete(target: ISocialMob): Promise<boolean> {
        await BaseApi.httpRequest.delete(`/social_mob/${target.id}`);
        return true;
    }

    static async join(target: ISocialMob): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.post(`/social_mob/${target.id}/join`);
        return new SocialMob(response.data);
    }

    static async leave(target: ISocialMob): Promise<SocialMob> {
        let response = await BaseApi.httpRequest.post(`/social_mob/${target.id}/leave`);
        return new SocialMob(response.data);
    }
}
