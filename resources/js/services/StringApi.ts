export class StringApi {
    static isUrl(possibleUrl: string): boolean {
        try {
            new URL(possibleUrl);
            return true;
        } catch {
            return false;
        }
    }
}
