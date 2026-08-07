export interface CommentContentSegment {
    type: 'text' | 'image';
    value: string;
}

const URL_PATTERN = /https?:\/\/\S+/g;
const IMAGE_EXTENSION_PATTERN = /\.(gif|png|jpe?g|webp)(?:[?#]\S*)?$/i;
const TRAILING_PUNCTUATION_PATTERN = /[.,!?;:)\]]+$/;

/** Splits comment text into text/image segments so image & GIF URLs can be rendered as `<img>`s. */
export function parseCommentContent(content: string): CommentContentSegment[] {
    const segments: CommentContentSegment[] = [];
    let lastIndex = 0;

    for (const match of content.matchAll(URL_PATTERN)) {
        const matchStart = match.index ?? 0;
        const trailingPunctuation = match[0].match(TRAILING_PUNCTUATION_PATTERN)?.[0] ?? '';
        const url = trailingPunctuation ? match[0].slice(0, -trailingPunctuation.length) : match[0];

        if (!IMAGE_EXTENSION_PATTERN.test(url)) continue;

        if (matchStart > lastIndex) {
            segments.push({ type: 'text', value: content.slice(lastIndex, matchStart) });
        }
        segments.push({ type: 'image', value: url });
        lastIndex = matchStart + url.length;
    }

    if (lastIndex < content.length || segments.length === 0) {
        segments.push({ type: 'text', value: content.slice(lastIndex) });
    }

    return segments;
}
