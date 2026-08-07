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

    it('strips a trailing apostrophe, which the shared url grammar counts as punctuation', () => {
        const result = parseCommentContent("here's mine: https://example.com/funny.gif'");

        expect(result).toEqual([
            { type: 'text', value: "here's mine: " },
            { type: 'image', value: 'https://example.com/funny.gif' },
            { type: 'text', value: "'" },
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

    it('is case-insensitive on the URL scheme', () => {
        const result = parseCommentContent('Https://example.com/funny.gif');
        expect(result).toEqual([{ type: 'image', value: 'Https://example.com/funny.gif' }]);
    });

    it.each(['pdf', 'mp4', 'exe', 'svg'])('does not treat a .%s URL as an image', (extension) => {
        const url = `https://example.com/file.${extension}`;
        expect(parseCommentContent(url)).toEqual([{ type: 'text', value: url }]);
    });

    it('splits out an image URL wrapped in markdown-style parentheses', () => {
        const result = parseCommentContent('link: (https://example.com/funny.gif) neat right?');

        expect(result).toEqual([
            { type: 'text', value: 'link: (' },
            { type: 'image', value: 'https://example.com/funny.gif' },
            { type: 'text', value: ') neat right?' },
        ]);
    });

    it('keeps an image URL as plain text when images are not allowed', () => {
        const content = 'look at this https://example.com/funny.gif so good';
        expect(parseCommentContent(content, false)).toEqual([{ type: 'text', value: content }]);
    });
});
