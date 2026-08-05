import { ITag } from '@/types';
import BaseApi from './BaseApi';

export class TagsApi extends BaseApi {
    static async index(): Promise<ITag[]> {
        const response = await BaseApi.httpRequest.get<ITag[]>(`/tags`);
        return response.data;
    }
}
