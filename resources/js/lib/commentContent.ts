import { findUrls } from '@/lib/linkify';

export interface CommentContentSegment {
    type: 'text' | 'image';
    value: string;
}

const IMAGE_EXTENSION_PATTERN = /\.(gif|png|jpe?g|webp)(?:[?#]\S*)?$/i;

/**
 * Splits comment text into text/image segments so image & GIF URLs can be rendered as `<img>`s.
 * Finding the urls is `linkify`'s job; all this adds is deciding which of them are images.
 */
export function parseCommentContent(content: string): CommentContentSegment[] {
    const segments: CommentContentSegment[] = [];
    let lastIndex = 0;

    for (const url of findUrls(content)) {
        if (!IMAGE_EXTENSION_PATTERN.test(url.value)) continue;

        if (url.start > lastIndex) {
            segments.push({ type: 'text', value: content.slice(lastIndex, url.start) });
        }
        segments.push({ type: 'image', value: url.value });
        lastIndex = url.start + url.value.length;
    }

    // Unlike `toTextSegments`, an empty comment still yields one (empty) text segment so
    // callers can render the result without special-casing nothing-to-show.
    if (lastIndex < content.length || segments.length === 0) {
        segments.push({ type: 'text', value: content.slice(lastIndex) });
    }

    return segments;
}
