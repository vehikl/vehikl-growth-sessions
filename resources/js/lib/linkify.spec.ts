import { toTextSegments } from './linkify';

describe('toTextSegments', () => {
    it('returns nothing for an empty string', () => {
        expect(toTextSegments('')).toEqual([]);
    });

    it('returns text with no links as a single segment', () => {
        expect(toTextSegments('Pairing on the auth refactor')).toEqual([{ type: 'text', value: 'Pairing on the auth refactor' }]);
    });

    test.each`
        url
        ${'https://meet.google.com'}
        ${'https://meet.google.com/an-ex-am-ple?authuser=0&pli=1'}
        ${'http://notsecure.com'}
        ${'https://zoom.us/j/123123145'}
        ${'https://www.oldstyle.com'}
        ${'mailto:someone@vehikl.com'}
    `('turns $url into a link segment', ({ url }) => {
        expect(toTextSegments(`Join at ${url} please`)).toEqual([
            { type: 'text', value: 'Join at ' },
            { type: 'link', value: url },
            { type: 'text', value: ' please' },
        ]);
    });

    it('leaves punctuation wrapped around a url out of the link', () => {
        expect(toTextSegments('It is happening (https://growth.vehikl.com)!!! Right now!')).toEqual([
            { type: 'text', value: 'It is happening (' },
            { type: 'link', value: 'https://growth.vehikl.com' },
            { type: 'text', value: ')!!! Right now!' },
        ]);
    });

    it('links every url in the text', () => {
        const segments = toTextSegments('Either https://growth.vehikl.com or https://vehikl.com works');

        expect(segments.filter((segment) => segment.type === 'link')).toEqual([
            { type: 'link', value: 'https://growth.vehikl.com' },
            { type: 'link', value: 'https://vehikl.com' },
        ]);
    });

    it('preserves the original text exactly', () => {
        const text = 'Line one\n\n  Indented https://vehikl.com trailing  ';

        expect(
            toTextSegments(text)
                .map((segment) => segment.value)
                .join(''),
        ).toEqual(text);
    });

    it('does not link schemes that are not browsable', () => {
        expect(toTextSegments('javascript:alert(1)')).toEqual([{ type: 'text', value: 'javascript:alert(1)' }]);
        expect(toTextSegments('Reminder: bring notes')).toEqual([{ type: 'text', value: 'Reminder: bring notes' }]);
        expect(toTextSegments('data:text/html;base64,PHA+aGk8L3A+')).toEqual([{ type: 'text', value: 'data:text/html;base64,PHA+aGk8L3A+' }]);
    });

    it('does not link a bare domain without a scheme', () => {
        expect(toTextSegments('see vehikl.com')).toEqual([{ type: 'text', value: 'see vehikl.com' }]);
    });
});
