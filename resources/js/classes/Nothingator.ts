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

    private static wordsAvailable: string[] = Object.values(Nothingator.nothingInDifferentLanguages);

    static random(): string {
        if (Nothingator.wordsAvailable.length === 0) {
            Nothingator.wordsAvailable = Object.values(Nothingator.nothingInDifferentLanguages);
        }
        let randomIndex = Math.floor(Math.random() * Nothingator.wordsAvailable.length);
        return Nothingator.wordsAvailable.splice(randomIndex, 1)[0];
    }
}
