import {mount, Wrapper} from "@vue/test-utils";
import LocationRenderer from './LocationRenderer.vue';
import flushPromises from "flush-promises";

describe('LocationRenderer', () => {
    let wrapper: Wrapper<LocationRenderer>;
    it('renders a simple location as text', () => {
        const simpleMobLocation: string = 'This is a simple mob location';
        wrapper = mount(LocationRenderer, {propsData: {locationString: simpleMobLocation}});

        expect(wrapper.find('span').text()).toContain(simpleMobLocation);
    })

    it('renders url location as a link', async () => {
        const urlLocation: string = 'https://social.vehikl.com';
        wrapper = mount(LocationRenderer, {propsData: {locationString: urlLocation}});

        expect(wrapper.find('a.underline').element).toHaveAttribute('href', urlLocation);
    })
})
