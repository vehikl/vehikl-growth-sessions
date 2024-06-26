import BaseApi from './BaseApi';
import {ITag} from "../types";

export class TagsApi extends BaseApi {
    static async index(): Promise<ITag[]> {
        let response = await BaseApi.httpRequest.get<ITag[]>(`/tags`);
        return response.data;
    }
}
