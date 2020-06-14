import {Nothingator} from './Nothingator';

describe('Nothingator', () => {
    it('Does not return the same word until no more words are available', () => {
        Math.random = jest.fn().mockReturnValue(0);

        let wordsReceived: string[] = [];
        for (let i = 0; i < Nothingator.nothingInDifferentLanguages.length; i++) {
            let newWord = Nothingator.random();
            expect(wordsReceived.includes(newWord)).toBe(false);
            wordsReceived.push(newWord);
        }
    });

    it('Can be called multiple times and always return a word', () => {
        for (let i = 0; i < 50; i++) {
            expect(Nothingator.random()).not.toBeUndefined();
        }
    });
});
