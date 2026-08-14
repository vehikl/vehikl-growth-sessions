export interface ITextSegment {
    // `string`, not a `'text' | 'link' | 'image'` union: sourced from the backend, and JSON can't carry TS literal types.
    type: string;
    value: string;
    // Only present on 'link' segments; the backend decides this (see TextSegmentParser::SCHEMES).
    opens_in_new_tab?: boolean;
}
