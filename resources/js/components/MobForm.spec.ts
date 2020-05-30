import {mount, Wrapper} from '@vue/test-utils';
import MobForm from './MobForm.vue';
import {IStoreSocialMobRequest, IUser} from '../types';
import flushPromises from 'flush-promises';
import {SocialMobApi} from '../services/SocialMobApi';

const user: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    id: 987,
    name: 'Jack Bauer'
};
const startDate: string = "2020-06-25";

describe('MobForm', () => {
    let wrapper: Wrapper<MobForm>;

    beforeEach(() => {
        wrapper = mount(MobForm, {propsData: {owner: user, startDate}});
        SocialMobApi.store = jest.fn().mockResolvedValue({});
        SocialMobApi.update = jest.fn().mockResolvedValue({});
    });

    it('allows a mob to be created', async () => {
        const chosenTopic = 'The chosen topic';
        const chosenLocation = 'Somewhere over the rainbow';
        const chosenTime = "4:45 pm";

        wrapper.vm.$data.startTime = chosenTime;
        wrapper.find('#topic').setValue(chosenTopic);
        wrapper.find('#location').setValue(chosenLocation);
        await wrapper.vm.$nextTick();
        wrapper.find('button[type="submit"]').trigger('click');
        await flushPromises();

        const expectedPayload: IStoreSocialMobRequest = {
            location: chosenLocation,
            date: startDate,
            start_time: chosenTime,
            end_time: '05:00 pm',
            topic: chosenTopic
        };
        expect(SocialMobApi.store).toHaveBeenCalledWith(expectedPayload);
    });

    it('emits submitted event on success', async () => {
        wrapper.vm.$data.startTime = '3:30 pm';
        wrapper.find('#topic').setValue('Anything');
        wrapper.find('#location').setValue('Anywhere');
        await wrapper.vm.$nextTick();
        wrapper.find('button[type="submit"]').trigger('click');
        await flushPromises();
        expect(wrapper.emitted('submitted')).toBeTruthy();
    });

    it('starts with the submit button disabled', () => {
        expect(wrapper.find('button[type="submit"]').element).toBeDisabled();
    });

    it('enables the submit button when all required fields are filled', async () => {
        wrapper.vm.$data.startTime = '3:30 pm';
        wrapper.find('#topic').setValue('Anything');
        wrapper.find('#location').setValue('Anywhere');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('button[type="submit"]').element).not.toBeDisabled();
    });
});
