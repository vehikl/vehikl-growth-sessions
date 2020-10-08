import {mount, Wrapper} from "@vue/test-utils";
import LocationRenderer from './LocationRenderer.vue';

describe('LocationRenderer', () => {
    let wrapper: Wrapper<LocationRenderer>;
    it('renders a simple location as text', () => {
        const simpleMobLocation: string = 'This is a simple mob location';
        wrapper = mount(LocationRenderer, {propsData: {locationString: simpleMobLocation}});

        expect(wrapper.find('span').text()).toContain(simpleMobLocation);
    });

    test.each`
    url
    ${'https://meet.google.com'}
    ${'https://meet.google.com/an-ex-am-ple?authuser=0&pli=1'}
    ${'http://notsecure.com'}
    ${'https://zoom.us/j/123123145'}
    ${'https://www.oldstyle.com'}
    `('renders $url in an anchor tag', ({url}) => {
        const complexLocation: string = `It is happening at ${url} ... Right now!`;
        wrapper = mount(LocationRenderer, {propsData: {locationString: complexLocation}});

        expect(wrapper.find('a.underline').element).toHaveAttribute('href', url);
    });

    it('renders a url in an anchor tag even if there is clutter around', () => {
        const url: string = "https://social.vehikl.com";
        const complexLocation: string = `It is happening (${url})!!! Right now!`;
        wrapper = mount(LocationRenderer, {propsData: {locationString: complexLocation}});

        expect(wrapper.find('a.underline').element).toHaveAttribute('href', url);
    });

    it('renders 2 urls as separate links', async () => {
        const url = 'https://social.vehikl.com';
        const complexLocation: string = `I can't decide between ${url} and ${url}`;
        wrapper = mount(LocationRenderer, {propsData: {locationString: complexLocation}});

        expect(wrapper.findAll('a.underline')).toHaveLength(2);
        for (const anchorWrapper of wrapper.findAll('a.underline').wrappers) {
            expect(anchorWrapper.element).toHaveAttribute('href', url);
        }
    });
})
