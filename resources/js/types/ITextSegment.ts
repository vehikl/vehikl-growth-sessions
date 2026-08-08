export interface ITextSegment {
    // Sourced from the backend, so this is left as `string` rather than a `'text' | 'link' | 'image'`
    // union - JSON fixtures/API responses can't carry TS literal types.
    type: string;
    value: string;
}
