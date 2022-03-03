import BaseApi from './BaseApi';
import {IAnyDesk} from "../types";

export class AnydesksApi extends BaseApi {
    static async getAllAnyDesks(): Promise<IAnyDesk[]> {
        let response = await BaseApi.httpRequest.get<IAnyDesk[]>(`/anydesks`);
        return response.data;
    }
}
