import { INotificationIndexResponse } from '@/types';
import BaseApi from './BaseApi';

export const notificationResource = 'notifications';

export class NotificationApi extends BaseApi {
    static async index(): Promise<INotificationIndexResponse> {
        const response = await BaseApi.httpRequest.get<INotificationIndexResponse>(`/${notificationResource}`);
        return response.data;
    }

    static async markRead(): Promise<void> {
        await BaseApi.httpRequest.post(`/${notificationResource}/read`);
    }
}
