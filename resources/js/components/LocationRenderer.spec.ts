import {mount, Wrapper} from "@vue/test-utils";
import LocationRenderer from './LocationRenderer.vue';

describe('LocationRenderer', () => {
    it('renders a simple location', () => {
        const simpleMobLocation: string = 'This is a simple mob location';
        const wrapper: Wrapper<LocationRenderer> = mount(LocationRenderer, { propsData: { locationString: simpleMobLocation } });

        expect(wrapper.text()).toContain(simpleMobLocation);
    })
})
