import {mount, Wrapper} from "@vue/test-utils"
import LocationRenderer from "./LocationRenderer.vue"

describe('LocationRenderer', () => {
    let wrapper: Wrapper<LocationRenderer>;
    it('renders a simple location as text', () => {
        const simpleLocation: string = 'This is a simple location';
        wrapper = mount(LocationRenderer, {propsData: {locationString: simpleLocation}});

        expect(wrapper.find('span').text()).toContain(simpleLocation);
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

        expect(wrapper.find("a.underline").attributes("href")).toEqual(url)
    });

    it('renders a url in an anchor tag even if there is clutter around', () => {
        const url: string = "https://growth.vehikl.com";
        const complexLocation: string = `It is happening (${url})!!! Right now!`;
        wrapper = mount(LocationRenderer, {propsData: {locationString: complexLocation}});

        expect(wrapper.find("a.underline").attributes("href")).toEqual(url)
    });

    it('renders 2 urls as separate links', async () => {
        const url = "https://growth.vehikl.com"
        const complexLocation: string = `I can't decide between ${url} and ${url}`
        wrapper = mount(LocationRenderer, {propsData: {locationString: complexLocation}})

        expect(wrapper.findAll("a.underline")).toHaveLength(2)
        wrapper.findAll("a.underline").forEach((anchorWrapper) => {
            expect(anchorWrapper.attributes("href")).toEqual(url)
        })
    });

    it('displays a trailing parenthese in the location', () => {
        wrapper = mount(LocationRenderer, {propsData: {locationString: 'Geralt (of Rivia)'}});
        expect(wrapper.text()).toContain('Geralt (of Rivia)');
    });

    it('prepends https on urls that are missing prefix', () => {
        const url = 'meet.google.com/snickres-asdf-qwer'

        const complexLocation: string = `It is happening at ${url} ... Right now!`;
        wrapper = mount(LocationRenderer, {propsData: {locationString: complexLocation}});

        expect(wrapper.find("a.underline").attributes("href")).toEqual(`https://${url}`)
    })
})
