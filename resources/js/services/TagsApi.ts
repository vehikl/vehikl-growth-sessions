import BaseApi from './BaseApi';
import {IAnyDesk} from "../types";

export class TagsApi extends BaseApi {
    static async index(): Promise<IAnyDesk[]> {
        let response = await BaseApi.httpRequest.get<IAnyDesk[]>(`/tags`);
        return response.data;
    }
}
