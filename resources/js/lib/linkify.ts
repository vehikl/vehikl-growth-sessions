export interface TextSegment {
    type: 'text' | 'link';
    value: string;
}

/** Schemes we are willing to hand to the browser from user-authored text. */
const LINKABLE_PROTOCOLS = ['http:', 'https:', 'mailto:'];

/** Punctuation that reads as sentence structure rather than part of a url. */
const TRAILING_PUNCTUATION = /[.,!?;:'")\]}]+$/;

/**
 * Splits free text into plain-text and link segments. Every character of the
 * input lands in exactly one segment, so callers can render the text verbatim.
 */
export function toTextSegments(text: string): TextSegment[] {
    const segments: TextSegment[] = [];
    let consumedUpTo = 0;

    for (const match of text.matchAll(/\S+/g)) {
        const url = findUrl(match[0]);
        if (!url) continue;

        const urlStart = (match.index ?? 0) + url.offset;
        pushText(segments, text.slice(consumedUpTo, urlStart));
        segments.push({ type: 'link', value: url.value });
        consumedUpTo = urlStart + url.value.length;
    }

    pushText(segments, text.slice(consumedUpTo));

    return segments;
}

function pushText(segments: TextSegment[], value: string): void {
    if (value) {
        segments.push({ type: 'text', value });
    }
}

function findUrl(token: string): { value: string; offset: number } | null {
    const offset = token.search(/[A-Za-z]/);
    if (offset < 0) {
        return null;
    }

    const candidate = token.slice(offset).replace(TRAILING_PUNCTUATION, '');
    if (!candidate) {
        return null;
    }

    let parsed: URL;
    try {
        parsed = new URL(candidate);
    } catch {
        return null;
    }

    if (!LINKABLE_PROTOCOLS.includes(parsed.protocol)) {
        return null;
    }

    // The raw candidate becomes the href so the link text and its target stay identical;
    // URL normalisation would append a trailing slash to a bare origin.
    return { value: candidate, offset };
}
