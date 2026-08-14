<?php

namespace App\Support;

/**
 * Splits free-form text (comments, session topics, session locations) into text/link/image
 * segments. Recognizes http(s), mailto, and tel urls. Rendering anything as an interactive link or
 * image is a trust boundary gated on the author (see `parse()`) - an untrusted author's urls stay
 * inert plain text.
 */
class TextSegmentParser
{
    // The opening alternation lists each recognized scheme literally so the lookahead below can
    // reuse the exact same alternatives. `https?:\/\/` keeps the `://` requirement for http(s) so a
    // bare `http:`/`https:` substring inside another url's own query or path (e.g. `?q=http:test`,
    // `/wiki/HTTP:_header`) doesn't get mistaken for a second url starting mid-match.
    private const SCHEMES = '(?:https?:\/\/|mailto:|tel:)';

    // `(?!SCHEMES)` stops a match right before a second recognized-scheme url starts, so two urls
    // joined by any character (or none) don't merge into one broken match. `(?<=[=\/])` overrides
    // that stop when the second scheme immediately follows a `=` or `/` - a query parameter value
    // (e.g. `?redirect=https://...`) or a path segment that merely looks like a scheme (e.g.
    // `/contact/tel:5551234`) rather than two urls butted together, so the outer url keeps consuming
    // through it instead of being cut in half. `"'<>|` and backtick are excluded outright since
    // they're never valid unencoded in a url and commonly wrap or separate a pasted link (quotes,
    // markdown, code spans) - unlike e.g. a comma, which is legal inside a url's own path or query.
    // The leading scheme is captured so the matched literal doesn't need to be re-derived afterward.
    // `/u` puts matching in Unicode mode so `\s` also treats non-breaking spaces (and other Unicode
    // whitespace) as url boundaries, matching the deleted JS parser's behavior.
    private const URL_PATTERN = '/('.self::SCHEMES.')(?:(?:(?<=[=\/])|(?!'.self::SCHEMES.'))[^\s"\'<>`|])+/iu';

    private const IMAGE_EXTENSION_PATTERN = '/\.(gif|png|jpe?g|webp)$/i';

    // Used to classify a lone image candidate. `!` and `:` are deliberately excluded: unlike the
    // rest of this set they're plausible literal characters in a signed CDN url's query string, and
    // there's no reliable way to tell that apart from sentence punctuation, so we err on the side of
    // not corrupting the url. That exception only makes sense when the url actually has a query
    // string to protect - see IMAGE_TRAILING_PUNCTUATION_CHARS_WITHOUT_QUERY_STRING below.
    private const LENIENT_TRAILING_PUNCTUATION_CHARS = ['.', ',', ';', '?', ']', '"', "'"];

    // Used instead of LENIENT_TRAILING_PUNCTUATION_CHARS to classify a lone image candidate that has
    // no `?` in it at all. With no query string to protect, a trailing `!` or `:` directly after the
    // extension (e.g. "check this gif!") can only be sentence punctuation, so it's safe to strip -
    // otherwise `hasImageExtension()`'s path-suffix check never matches and the candidate is wrongly
    // demoted to a plain link.
    private const IMAGE_TRAILING_PUNCTUATION_CHARS_WITHOUT_QUERY_STRING = [...self::LENIENT_TRAILING_PUNCTUATION_CHARS, '!', ':'];

    // Used for links. Links aren't rendered as <img> tags, so there's no signed-CDN-query-string
    // concern justifying an exception for `!`/`:` - both are stripped here, along with `}`.
    private const STRICT_TRAILING_PUNCTUATION_CHARS = [...self::LENIENT_TRAILING_PUNCTUATION_CHARS, '!', ':', '}'];

    /**
     * Splits text into text/link/image segments. $isTrustedAuthor gates *all* interactive rendering
     * (links and images alike) on the author's trust level: a clickable link can be used to phish an
     * unsuspecting reader, and an <img> makes every viewer's browser auto-fetch it, which lets an
     * untrusted poster use it as a tracking pixel. When $isTrustedAuthor is false, no url candidate
     * becomes its own segment (link or image) - each is left untouched as part of its surrounding
     * text segment, and never creates a text-segment boundary.
     *
     * $allowImages is a second, independent gate scoped to images only, checked only once
     * $isTrustedAuthor already allows interactive rendering at all - it exists so a trusted author's
     * content can still have image rendering suppressed on its own (e.g. session topic/location,
     * which are single-line fields with no room for an embedded image, but should still linkify).
     *
     * This is the single source of truth for both decisions - a disallowed candidate's url still
     * reaches clients as part of the surrounding text (it's not redacted; the raw content is visible
     * elsewhere in the payload regardless), but no client is ever told to render it as an <a>/<img>,
     * whether they go through this app's own frontend or hit the API directly.
     */
    public static function parse(string $content, bool $isTrustedAuthor, bool $allowImages = true): array
    {
        $segments = [];
        $lastIndex = 0;

        preg_match_all(self::URL_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $index => [$rawUrl, $matchStart]) {
            if (! $isTrustedAuthor) {
                continue;
            }

            $schemeLiteral = strtolower($matches[1][$index][0]);
            $isHttpScheme = $schemeLiteral === 'http://' || $schemeLiteral === 'https://';

            if ($isHttpScheme) {
                $imageTrailingPunctuationChars = str_contains($rawUrl, '?')
                    ? self::LENIENT_TRAILING_PUNCTUATION_CHARS
                    : self::IMAGE_TRAILING_PUNCTUATION_CHARS_WITHOUT_QUERY_STRING;
                $lenientUrl = self::stripTrailingPunctuation($rawUrl, $imageTrailingPunctuationChars);
            }

            if ($isHttpScheme && self::hasImageExtension($lenientUrl)) {
                if (! $allowImages) {
                    continue;
                }

                $type = 'image';
                $url = $lenientUrl;
            } else {
                $strictUrl = self::stripTrailingPunctuation($rawUrl, self::STRICT_TRAILING_PUNCTUATION_CHARS);

                if (strlen($strictUrl) <= strlen($schemeLiteral)) {
                    continue;
                }

                $type = 'link';
                $url = $strictUrl;
            }

            if ($matchStart > $lastIndex) {
                $segments[] = ['type' => 'text', 'value' => substr($content, $lastIndex, $matchStart - $lastIndex)];
            }

            // Only 'link' carries this: it's the frontend's sole consumer, deciding target=_blank on
            // an <a>, which doesn't apply to the <img> an 'image' segment renders as. This is the
            // single source of truth for that scheme allowlist - see SCHEMES above - so the frontend
            // never re-derives it and can't drift from what's actually recognized here.
            $segments[] = $type === 'link'
                ? ['type' => $type, 'value' => $url, 'opens_in_new_tab' => $isHttpScheme]
                : ['type' => $type, 'value' => $url];
            $lastIndex = $matchStart + strlen($url);
        }

        if ($lastIndex < strlen($content) || empty($segments)) {
            $segments[] = ['type' => 'text', 'value' => substr($content, $lastIndex)];
        }

        return $segments;
    }

    /** Whether the url's path (ignoring query/fragment) ends in a supported image extension. */
    private static function hasImageExtension(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return false;
        }

        return (bool) preg_match(self::IMAGE_EXTENSION_PATTERN, $path);
    }

    /**
     * Trims sentence/wrapping punctuation a human likely typed after a pasted url. A trailing `)` is
     * only trimmed when it's unbalanced (no matching `(` earlier in the url), matching how
     * GitHub-flavored Markdown autolinks handle "(see http://example.com/x.png)". This check is
     * shared by both punctuation policies - `$punctuationChars` only affects which non-`)` characters
     * are treated as trailing punctuation.
     */
    private static function stripTrailingPunctuation(string $url, array $punctuationChars): string
    {
        $end = strlen($url);
        $openParens = substr_count($url, '(');
        $closeParens = substr_count($url, ')');

        while ($end > 0) {
            $char = $url[$end - 1];

            if ($char === ')') {
                if ($closeParens <= $openParens) {
                    break;
                }
            } elseif (! in_array($char, $punctuationChars, true)) {
                break;
            }

            if ($char === '(') {
                $openParens--;
            } elseif ($char === ')') {
                $closeParens--;
            }

            $end--;
        }

        return substr($url, 0, $end);
    }
}
