import {mount, Wrapper} from '@vue/test-utils';
import CreateMob from './CreateMob.vue';
import {IUser} from '../types';
import MockAdapter from 'axios-mock-adapter';
import axios from 'axios';

const user: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    id: 987,
    name: 'Jack Bauer'
};

describe('CreateMob', () => {
    let wrapper: Wrapper<CreateMob>;
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
        mockBackend.onPost('/social_mob').reply(201);
        wrapper = mount(CreateMob, {propsData: {owner: user}});
    });

    afterEach(() => {
       mockBackend.restore();
    });

    it('allows a mob to be created', async () => {
        const chosenTopic = 'The chosen topic';
        const chosenLocation = 'Somewhere over the rainbow';

        wrapper.find('#topic').setValue(chosenTopic);
        wrapper.find('#location').setValue(chosenLocation);
        wrapper.find('button[type="submit"]').trigger('click');
    });
});
