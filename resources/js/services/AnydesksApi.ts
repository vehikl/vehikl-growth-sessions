import { IAnyDesk } from '@/types';
import BaseApi from './BaseApi';

export class AnydesksApi extends BaseApi {
    static async getAllAnyDesks(): Promise<IAnyDesk[]> {
        const response = await BaseApi.httpRequest.get<IAnyDesk[]>(`/anydesks`);
        return response.data;
    }
}
