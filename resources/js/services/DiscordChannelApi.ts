import BaseApi from './BaseApi';
import {IDiscordChannel} from "../types/IDiscordChannel";

export const discordChannelResource = 'api/discord-channels';

export class DiscordChannelApi extends BaseApi {
    static async index(): Promise<Array<IDiscordChannel>> {
        const response = await BaseApi.httpRequest.get<Array<IDiscordChannel>>(`/${discordChannelResource}`);
        return response.data;
    }
}
