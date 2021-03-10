import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import {DiscordChannelApi, discordChannelResource} from './DiscordChannelApi';
import {IDiscordChannel} from "../types/IDiscordChannel";

const discordChannelsJson: Array<IDiscordChannel> = [
    {
        id: '1234567890',
        name: 'Channel One',
    },
    {
        id: '1234567891',
        name: 'Channel Two',
    }
];

describe('DiscordChannelApi', () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
    });

    afterEach(() => {
        mockBackend.restore();
    });

    it('returns an array of Discord channels', async () => {
        mockBackend.onGet(`${discordChannelResource}`).reply(200, discordChannelsJson);

        const result = await DiscordChannelApi.index();

        expect(result).toEqual(discordChannelsJson);
    });
});
