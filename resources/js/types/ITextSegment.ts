// The backend always includes `opens_in_new_tab` on a 'link' segment and never on any other type
// (see TextSegmentParser::parse()), so it's modeled as required on 'link' and absent elsewhere
// rather than optional on every variant.
export type ITextSegment = { type: 'text' | 'image'; value: string } | { type: 'link'; value: string; opens_in_new_tab: boolean };
