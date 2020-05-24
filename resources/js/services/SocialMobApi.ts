import BaseApi from './BaseApi';
import {ISocialMob, IStoreSocialMobRequest, IUpdateSocialMobRequest, IWeekMobs} from '../types';

export class SocialMobApi extends BaseApi {
    static async getAllMobsOfTheWeek(): Promise<IWeekMobs> {
        let response = await BaseApi.httpRequest.get<IWeekMobs>('social_mob/week');
        return response.data;
    }

    static async store(payload: IStoreSocialMobRequest): Promise<ISocialMob> {
        let response = await BaseApi.httpRequest.post<ISocialMob>('social_mob', payload);
        return response.data;
    }

    static async update(target: ISocialMob, payload: IUpdateSocialMobRequest): Promise<ISocialMob> {
        let response = await BaseApi.httpRequest.put<ISocialMob>(`social_mob/${target.id}`, payload);
        return response.data;
    }

    static async delete(target: ISocialMob): Promise<boolean> {
        await BaseApi.httpRequest.delete(`social_mob/${target.id}`);
        return true;
    }
}
