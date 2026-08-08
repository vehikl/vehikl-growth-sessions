import type { ITextSegment } from '@/types';
import { mount, type VueWrapper } from '@vue/test-utils';
import TextSegments from './TextSegments.vue';

describe('TextSegments', () => {
    let wrapper: VueWrapper;

    it('renders a text segment as plain text', () => {
        const segments: ITextSegment[] = [{ type: 'text', value: 'just some plain text' }];
        wrapper = mount(TextSegments, { propsData: { segments } });

        expect(wrapper.text()).toBe('just some plain text');
        expect(wrapper.find('a').exists()).toBe(false);
        expect(wrapper.find('img').exists()).toBe(false);
    });

    it('renders a link segment as an anchor', () => {
        const url = 'https://example.com';
        const segments: ITextSegment[] = [{ type: 'link', value: url }];
        wrapper = mount(TextSegments, { propsData: { segments } });

        expect(wrapper.find('a').attributes('href')).toBe(url);
        expect(wrapper.find('a').text()).toBe(url);
    });

    it('renders an image segment as an embedded image', () => {
        const imageUrl = 'https://example.com/funny.gif';
        const segments: ITextSegment[] = [{ type: 'image', value: imageUrl }];
        wrapper = mount(TextSegments, { propsData: { segments } });

        expect(wrapper.find('img').attributes('src')).toBe(imageUrl);
    });

    it('falls back to the exact raw url as plain, non-clickable text when an embedded image fails to load', async () => {
        const imageUrl = 'https://example.com/dead-link.gif';
        const segments: ITextSegment[] = [{ type: 'image', value: imageUrl }];
        wrapper = mount(TextSegments, { propsData: { segments } });

        await wrapper.find('img').trigger('error');

        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.find('a').exists()).toBe(false);
        expect(wrapper.text()).toBe(imageUrl);
    });

    it('does not suppress a repeated image url within the same segments array when one occurrence fails to load', async () => {
        const imageUrl = 'https://example.com/repeated.gif';
        const segments: ITextSegment[] = [
            { type: 'image', value: imageUrl },
            { type: 'text', value: ' and again ' },
            { type: 'image', value: imageUrl },
        ];
        wrapper = mount(TextSegments, { propsData: { segments } });

        const images = wrapper.findAll('img');
        await images[0].trigger('error');

        const remainingImages = wrapper.findAll('img');
        expect(remainingImages).toHaveLength(1);
        expect(remainingImages[0].attributes('src')).toBe(imageUrl);
    });

    describe('protocol-aware anchors', () => {
        it('opens http(s) links in a new tab with rel=noopener noreferrer', () => {
            const url = 'https://example.com';
            const segments: ITextSegment[] = [{ type: 'link', value: url }];
            wrapper = mount(TextSegments, { propsData: { segments } });

            const anchor = wrapper.find('a');
            expect(anchor.attributes('href')).toBe(url);
            expect(anchor.attributes('target')).toBe('_blank');
            expect(anchor.attributes('rel')).toBe('noopener noreferrer');
        });

        it('does not add target/rel to a mailto link', () => {
            const url = 'mailto:jane@example.com';
            const segments: ITextSegment[] = [{ type: 'link', value: url }];
            wrapper = mount(TextSegments, { propsData: { segments } });

            const anchor = wrapper.find('a');
            expect(anchor.attributes('href')).toBe(url);
            expect(anchor.attributes('target')).toBeUndefined();
            expect(anchor.attributes('rel')).toBeUndefined();
        });

        it('does not add target/rel to a tel link', () => {
            const url = 'tel:5551234';
            const segments: ITextSegment[] = [{ type: 'link', value: url }];
            wrapper = mount(TextSegments, { propsData: { segments } });

            const anchor = wrapper.find('a');
            expect(anchor.attributes('href')).toBe(url);
            expect(anchor.attributes('target')).toBeUndefined();
            expect(anchor.attributes('rel')).toBeUndefined();
        });

        it('still adds target/rel to an uppercase-scheme link', () => {
            const url = 'HTTP://EXAMPLE.COM';
            const segments: ITextSegment[] = [{ type: 'link', value: url }];
            wrapper = mount(TextSegments, { propsData: { segments } });

            const anchor = wrapper.find('a');
            expect(anchor.attributes('href')).toBe(url);
            expect(anchor.attributes('target')).toBe('_blank');
            expect(anchor.attributes('rel')).toBe('noopener noreferrer');
        });
    });

    it('renders an unknown segment type as plain text instead of disappearing', () => {
        const segments: ITextSegment[] = [{ type: 'something_new', value: 'foo' }];
        wrapper = mount(TextSegments, { propsData: { segments } });

        expect(wrapper.text()).toBe('foo');
        expect(wrapper.find('a').exists()).toBe(false);
        expect(wrapper.find('img').exists()).toBe(false);
    });

    it('preserves order and surrounding text across a mixed text/link/image segment array', () => {
        const linkUrl = 'https://example.com';
        const imageUrl = 'https://example.com/a.png';
        const segments: ITextSegment[] = [
            { type: 'text', value: 'hello ' },
            { type: 'link', value: linkUrl },
            { type: 'text', value: ' and ' },
            { type: 'image', value: imageUrl },
            { type: 'text', value: ' world' },
        ];
        wrapper = mount(TextSegments, { propsData: { segments } });

        expect(wrapper.text()).toBe(`hello ${linkUrl} and  world`);
        expect(wrapper.find('a').attributes('href')).toBe(linkUrl);
        expect(wrapper.find('img').attributes('src')).toBe(imageUrl);
    });
});
