<?php

namespace App\Support;

/**
 * Splits free-form text (comments, session topics, session locations) into text/link/image
 * segments. Recognizes http(s), mailto, and tel urls. Image-url detection runs server-side because
 * it's a trust boundary (see `parse()`) - general link detection is not, and applies to every
 * author.
 */
class TextSegmentParser
{
    // The opening alternation lists each recognized scheme literally so the lookahead below can
    // reuse the exact same alternatives. `https?:\/\/` keeps the `://` requirement for http(s) so a
    // bare `http:`/`https:` substring inside another url's own query or path (e.g. `?q=http:test`,
    // `/wiki/HTTP:_header`) doesn't get mistaken for a second url starting mid-match.
    private const SCHEMES = '(?:https?:\/\/|mailto:|tel:)';

    // `(?!SCHEMES)` stops a match right before a second recognized-scheme url starts, so two urls
    // joined by any character (or none) don't merge into one broken match. `"'<>|` and backtick are
    // excluded outright since they're never valid unencoded in a url and commonly wrap or separate a
    // pasted link (quotes, markdown, code spans) - unlike e.g. a comma, which is legal inside a url's
    // own path or query.
    private const URL_PATTERN = '/'.self::SCHEMES.'(?:(?!'.self::SCHEMES.')[^\s"\'<>`|])+/i';

    private const IMAGE_EXTENSION_PATTERN = '/\.(gif|png|jpe?g|webp)$/i';

    // Used to classify a lone image candidate. `!` and `:` are deliberately excluded: unlike the
    // rest of this set they're plausible literal characters in a signed CDN url's query string, and
    // there's no reliable way to tell that apart from sentence punctuation, so we err on the side of
    // not corrupting the url.
    private const LENIENT_TRAILING_PUNCTUATION_CHARS = ['.', ',', ';', '?', ']', '"', "'"];

    // Used for links. Links aren't rendered as <img> tags, so there's no signed-CDN-query-string
    // concern justifying an exception for `!`/`:` - both are stripped here, along with `}`.
    private const STRICT_TRAILING_PUNCTUATION_CHARS = [...self::LENIENT_TRAILING_PUNCTUATION_CHARS, '!', ':', '}'];

    private const SCHEME_LITERALS = ['https://', 'http://', 'mailto:', 'tel:'];

    /**
     * Splits text into text/link/image segments. $allowImages gates image-url rendering on the
     * author's trust level: rendering a url as an <img> makes every viewer's browser fetch it, which
     * lets an untrusted poster use it as a tracking pixel. When a candidate has an image extension
     * and $allowImages is false, it is left untouched as part of its surrounding text segment - it
     * never becomes its own segment (image or otherwise) and never creates a text-segment boundary.
     * General link detection (non-image urls, mailto:, tel:) is not a trust boundary and applies
     * regardless of $allowImages.
     *
     * This is the single source of truth for that decision - a disallowed image's url still reaches
     * clients as part of the surrounding text (it's not redacted; the raw content is visible elsewhere
     * in the payload regardless), but no client is ever told to render it as an <img>, whether they go
     * through this app's own frontend or hit the API directly.
     */
    public static function parse(string $content, bool $allowImages): array
    {
        $segments = [];
        $lastIndex = 0;

        preg_match_all(self::URL_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$rawUrl, $matchStart]) {
            $schemeLiteral = self::matchedSchemeLiteral($rawUrl);
            $isHttpScheme = $schemeLiteral === 'http://' || $schemeLiteral === 'https://';

            $lenientUrl = self::stripTrailingPunctuation($rawUrl, self::LENIENT_TRAILING_PUNCTUATION_CHARS);

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
            $segments[] = ['type' => $type, 'value' => $url];
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
     * Which of SCHEME_LITERALS the match actually starts with. A purely structural fact - which
     * regex alternative matched - independent of what trimming does to the string afterward.
     */
    private static function matchedSchemeLiteral(string $url): string
    {
        foreach (self::SCHEME_LITERALS as $scheme) {
            if (stripos($url, $scheme) === 0) {
                return $scheme;
            }
        }

        return '';
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

        while ($end > 0) {
            $char = $url[$end - 1];

            if ($char === ')') {
                $upToHere = substr($url, 0, $end);
                $openParens = substr_count($upToHere, '(');
                $closeParens = substr_count($upToHere, ')');
                if ($closeParens <= $openParens) {
                    break;
                }
            } elseif (! in_array($char, $punctuationChars, true)) {
                break;
            }

            $end--;
        }

        return substr($url, 0, $end);
    }
}
