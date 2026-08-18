import { INotification } from '@/types';
import BaseApi from './BaseApi';

export class NotificationApi extends BaseApi {
    /** The signed-in user's most recent notifications, newest first. The endpoint defaults to 10. */
    static async index(limit?: number): Promise<INotification[]> {
        const response = await BaseApi.httpRequest.get<INotification[]>('/notifications', {
            params: limit === undefined ? {} : { limit },
        });

        return response.data;
    }
}
