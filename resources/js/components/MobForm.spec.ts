import {mount, Wrapper} from '@vue/test-utils';
import MobForm from './MobForm.vue';
import {IUser} from '../types';
import MockAdapter from 'axios-mock-adapter';
import axios from 'axios';
import flushPromises from 'flush-promises';

const user: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    id: 987,
    name: 'Jack Bauer'
};
const startDate: string = "2020-06-25";

describe('MobForm', () => {
    let wrapper: Wrapper<MobForm>;
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
        mockBackend.onPost('/social_mob').reply(201);
        wrapper = mount(MobForm, {propsData: {owner: user, startDate}});
    });

    afterEach(() => {
       mockBackend.restore();
    });

    it('allows a mob to be created', async () => {
        const chosenTopic = 'The chosen topic';
        const chosenLocation = 'Somewhere over the rainbow';
        const chosenTime = "4:45 pm";

        wrapper.find('#topic').setValue(chosenTopic);
        wrapper.find('#location').setValue(chosenLocation);
        wrapper.vm.$data.time = chosenTime;
        wrapper.find('button[type="submit"]').trigger('click');
        await flushPromises();

        const dataSent = JSON.parse(mockBackend.history.post[0].data);
        expect(dataSent).toEqual({
            location: chosenLocation,
            start_time: `${startDate} ${chosenTime}`,
            topic: chosenTopic
        })
    });

    it('emits mob-created event on success', async () => {
        wrapper.find('#topic').setValue('Anything');
        wrapper.find('#location').setValue('Anywhere');
        wrapper.vm.$data.time = '3:30 pm';
        wrapper.find('button[type="submit"]').trigger('click');
        await flushPromises();

        expect(wrapper.emitted('mob-created')).toBeTruthy();
    })
});
