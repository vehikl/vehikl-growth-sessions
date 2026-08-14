export interface ITextSegment {
    // Sourced from the backend, so this is left as `string` rather than a `'text' | 'link' | 'image'`
    // union - JSON fixtures/API responses can't carry TS literal types.
    type: string;
    value: string;
    // Only present on 'link' segments. The backend owns the scheme allowlist (TextSegmentParser::SCHEMES)
    // that decides this, so the frontend renders it rather than re-deriving it from the url itself.
    opens_in_new_tab?: boolean;
}
