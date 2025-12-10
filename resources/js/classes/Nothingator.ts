export class Nothingator {
    static readonly nothingInDifferentLanguages: string[] = [
        'Empty',
        'Rien',
        'Nada',
        'Nichts',
        'Niente',
        'Нічого',
        'Nothing at all'
    ];

    static random(seed?: number | string): string {
        const words = [...Nothingator.nothingInDifferentLanguages];

        let randomValue: number;
        if (seed !== undefined) {
            // Use seed for deterministic selection
            const hash = this.simpleHash(seed.toString());
            randomValue = (hash % words.length) / words.length;
        } else {
            randomValue = Math.random();
        }

        const index = Math.floor(randomValue * words.length);
        return words[index];
    }

    private static simpleHash(str: string): number {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        return Math.abs(hash);
    }
}
