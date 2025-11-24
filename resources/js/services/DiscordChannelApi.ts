import BaseApi from './BaseApi';
import {IDiscordChannel} from "../types/IDiscordChannel";

export const discordChannelResource = 'api/discord-channels';

export class DiscordChannelApi extends BaseApi {
    static async index(): Promise<Array<IDiscordChannel>> {
        const response = await BaseApi.httpRequest.get<Array<IDiscordChannel>>(`/${discordChannelResource}`);
        return response.data;
    }
// HELLO FUTURE IAN
    //THIS IS RETURNING CHANNELS THAT ARE NOT OCCUPIED
    // MAL DID A 10-09 and a 10-10 and bot hwere marked occupied even though only the 9th was used
    // TODO: Fix it I guess
    static async occupied(date): Promise<Array<IDiscordChannel>> {
        const response = await BaseApi.httpRequest.get<Array<IDiscordChannel>>(`/${discordChannelResource}/${date}/occupied`);
        return response.data;
    }
}
