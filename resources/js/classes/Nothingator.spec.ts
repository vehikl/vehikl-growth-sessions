import {Nothingator} from "./Nothingator"

describe('Nothingator', () => {
    it('Can be called multiple times and always return a word', () => {
        for (let i = 0; i < 50; i++) {
            expect(Nothingator.random()).not.toBeUndefined();
        }
    });

    it('Returns a word from the available languages', () => {
        const word = Nothingator.random();
        expect(Nothingator.nothingInDifferentLanguages).toContain(word);
    });

    describe('with seed parameter', () => {
        it('Returns the same word for the same seed', () => {
            const seed = '2020-01-15';
            const word1 = Nothingator.random(seed);
            const word2 = Nothingator.random(seed);
            const word3 = Nothingator.random(seed);

            expect(word1).toBe(word2);
            expect(word2).toBe(word3);
        });

        it('Returns different words for different seeds', () => {
            const word1 = Nothingator.random('2020-01-15');
            const word2 = Nothingator.random('2020-01-16');
            const word3 = Nothingator.random('2020-01-17');

            // All should be valid words
            expect(Nothingator.nothingInDifferentLanguages).toContain(word1);
            expect(Nothingator.nothingInDifferentLanguages).toContain(word2);
            expect(Nothingator.nothingInDifferentLanguages).toContain(word3);

            // Note: There's a small chance some might be the same due to hash collisions,
            // but with 7 words and good hash distribution, at least one pair should differ
            const allSame = word1 === word2 && word2 === word3;
            expect(allSame).toBe(false);
        });

        it('Works with numeric seeds', () => {
            const word1 = Nothingator.random(123);
            const word2 = Nothingator.random(123);

            expect(word1).toBe(word2);
            expect(Nothingator.nothingInDifferentLanguages).toContain(word1);
        });
    });
});
