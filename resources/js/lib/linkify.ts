export interface TextSegment {
    type: 'text' | 'link';
    value: string;
}

/** A url located in free text, and where in that text it starts. */
export interface FoundUrl {
    value: string;
    start: number;
}

/** Schemes we are willing to hand to the browser from user-authored text. */
const LINKABLE_PROTOCOLS = ['http:', 'https:', 'mailto:', 'tel:'];

/** Schemes whose body is an opaque path rather than a host, so `new URL` accepts an empty one. */
const OPAQUE_PROTOCOLS = ['mailto:', 'tel:'];

/** Punctuation that reads as sentence structure rather than part of a url. */
const TRAILING_PUNCTUATION = /[.,!?;:'")\]}]+$/;

/**
 * Locates every linkable url in free text. This is the single url grammar in the app —
 * anything that needs to find urls in user-authored text goes through here rather than
 * growing a second, subtly different one.
 */
export function findUrls(text: string): FoundUrl[] {
    const found: FoundUrl[] = [];

    for (const match of text.matchAll(/\S+/g)) {
        const url = findUrl(match[0]);
        if (!url) continue;

        found.push({ value: url.value, start: (match.index ?? 0) + url.offset });
    }

    return found;
}

/**
 * Splits free text into plain-text and link segments. Every character of the
 * input lands in exactly one segment, so callers can render the text verbatim.
 */
export function toTextSegments(text: string): TextSegment[] {
    const segments: TextSegment[] = [];
    let consumedUpTo = 0;

    for (const url of findUrls(text)) {
        pushText(segments, text.slice(consumedUpTo, url.start));
        segments.push({ type: 'link', value: url.value });
        consumedUpTo = url.start + url.value.length;
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

    // `new URL('tel:')` parses happily, and a link to nothing is worse than plain text.
    if (OPAQUE_PROTOCOLS.includes(parsed.protocol) && !parsed.pathname) {
        return null;
    }

    // The raw candidate becomes the href so the link text and its target stay identical;
    // URL normalisation would append a trailing slash to a bare origin.
    return { value: candidate, offset };
}
