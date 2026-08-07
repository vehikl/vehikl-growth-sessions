import { parseCommentContent } from '@/lib/commentContent';

describe('parseCommentContent', () => {
    it('returns a single text segment when there is no URL', () => {
        expect(parseCommentContent('just some plain text')).toEqual([{ type: 'text', value: 'just some plain text' }]);
    });

    it('returns a single empty text segment for empty content', () => {
        expect(parseCommentContent('')).toEqual([{ type: 'text', value: '' }]);
    });

    it('does not treat a non-image URL as an image', () => {
        expect(parseCommentContent('check this out https://example.com/page')).toEqual([
            { type: 'text', value: 'check this out https://example.com/page' },
        ]);
    });

    it.each(['gif', 'png', 'jpg', 'jpeg', 'webp'])('renders a lone %s URL as an image segment', (extension) => {
        const url = `https://example.com/image.${extension}`;
        expect(parseCommentContent(url)).toEqual([{ type: 'image', value: url }]);
    });

    it('splits surrounding text from an embedded image URL', () => {
        const result = parseCommentContent('look at this https://example.com/funny.gif so good');

        expect(result).toEqual([
            { type: 'text', value: 'look at this ' },
            { type: 'image', value: 'https://example.com/funny.gif' },
            { type: 'text', value: ' so good' },
        ]);
    });

    it('handles multiple image URLs in the same comment', () => {
        const result = parseCommentContent('https://example.com/one.gif and https://example.com/two.png');

        expect(result).toEqual([
            { type: 'image', value: 'https://example.com/one.gif' },
            { type: 'text', value: ' and ' },
            { type: 'image', value: 'https://example.com/two.png' },
        ]);
    });

    it('strips trailing sentence punctuation from the URL', () => {
        const result = parseCommentContent('lol check this out https://example.com/funny.gif.');

        expect(result).toEqual([
            { type: 'text', value: 'lol check this out ' },
            { type: 'image', value: 'https://example.com/funny.gif' },
            { type: 'text', value: '.' },
        ]);
    });

    it('keeps a query string that is part of the image URL', () => {
        const url = 'https://example.com/funny.gif?cid=123';
        expect(parseCommentContent(url)).toEqual([{ type: 'image', value: url }]);
    });

    it('is case-insensitive on the extension', () => {
        const url = 'https://example.com/funny.GIF';
        expect(parseCommentContent(url)).toEqual([{ type: 'image', value: url }]);
    });
});
