import type { ITextSegment } from '@/types';
import { mount, type VueWrapper } from '@vue/test-utils';
import LocationRenderer from './LocationRenderer.vue';

describe('LocationRenderer', () => {
    let wrapper: VueWrapper;

    it('renders a text segment as plain text', () => {
        const segments: ITextSegment[] = [{ type: 'text', value: 'This is a simple location' }];
        wrapper = mount(LocationRenderer, { propsData: { segments } });

        expect(wrapper.find('span').text()).toContain('This is a simple location');
    });

    it('renders a link segment in an anchor tag', () => {
        const url = 'https://meet.google.com';
        const segments: ITextSegment[] = [
            { type: 'text', value: 'It is happening at ' },
            { type: 'link', value: url },
            { type: 'text', value: ' ... Right now!' },
        ];
        wrapper = mount(LocationRenderer, { propsData: { segments } });

        expect(wrapper.find('a.gs-accent-text').attributes('href')).toEqual(url);
    });

    it('renders multiple link segments as separate links', () => {
        const url = 'https://growth.vehikl.com';
        const segments: ITextSegment[] = [
            { type: 'text', value: "I can't decide between " },
            { type: 'link', value: url },
            { type: 'text', value: ' and ' },
            { type: 'link', value: url },
        ];
        wrapper = mount(LocationRenderer, { propsData: { segments } });

        expect(wrapper.findAll('a.gs-accent-text')).toHaveLength(2);
        wrapper.findAll('a.gs-accent-text').forEach((anchorWrapper) => {
            expect(anchorWrapper.attributes('href')).toEqual(url);
        });
    });

    it('passes an image segment through to an embedded image', () => {
        const imageUrl = 'https://example.com/room.png';
        const segments: ITextSegment[] = [{ type: 'image', value: imageUrl }];
        wrapper = mount(LocationRenderer, { propsData: { segments } });

        expect(wrapper.find('img').attributes('src')).toBe(imageUrl);
    });
});
