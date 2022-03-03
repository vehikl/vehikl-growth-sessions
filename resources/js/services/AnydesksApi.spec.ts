import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import anyDesksListJson from '../../../tests/fixtures/AnyDesksList.json';
import { AnydesksApi } from './AnydesksApi';

describe('AnydesksApi', () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
    })

    afterEach(() => {
       mockBackend.restore();
    });

    it('returns anydesks', async () => {
        mockBackend.onGet('anydesks').reply(200, anyDesksListJson);

        const result = await AnydesksApi.getAllAnyDesks();

        expect(result).toEqual(anyDesksListJson);
    });
})
