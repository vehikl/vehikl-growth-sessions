<?php

namespace App\Support;

/**
 * Splits free-form text (comments, session topics, session locations) into text/link/image
 * segments. Recognizes http(s), mailto, and tel urls. Rendering as a link/image is gated on the
 * author's trust (see parse()) - an untrusted author's urls stay inert plain text.
 */
class TextSegmentParser
{
    // `https?:\/\/` requires `://` so a bare `http:`/`https:` substring in another url's own query
    // or path (e.g. `?q=http:test`) isn't mistaken for a second url starting mid-match.
    private const SCHEMES = '(?:https?:\/\/|mailto:|tel:)';

    // `(?!SCHEMES)` stops a match before a second recognized scheme starts, so two urls glued
    // together don't merge into one broken match. `(?<=[=\/])` lets the outer url keep consuming
    // through a lookalike right after `=`/`/` (e.g. `?redirect=https://...`, `/contact/tel:5551234`)
    // instead of splitting on it. `"<>|` and backtick are excluded as never valid unencoded in a
    // url. Apostrophe is allowed - it's a valid RFC 3986 char (e.g. `Murphy's_Law`) and a wrapping
    // one still gets trimmed by stripTrailingPunctuation() below. `/u` treats non-breaking spaces as
    // boundaries too, matching the old JS parser.
    private const URL_PATTERN = '/('.self::SCHEMES.')(?:(?:(?<=[=\/])|(?!'.self::SCHEMES.'))[^\s"<>`|])+/iu';

    private const IMAGE_EXTENSION_PATTERN = '/\.(gif|png|jpe?g|webp)$/i';

    // `!`/`:` are excluded here since they're plausible chars in a signed CDN query string, with no
    // reliable way to tell that apart from sentence punctuation.
    private const LENIENT_TRAILING_PUNCTUATION_CHARS = ['.', ',', ';', '?', ']', '"', "'"];

    // Used when there's no query string to protect, so a trailing `!`/`:` can only be punctuation.
    private const IMAGE_TRAILING_PUNCTUATION_CHARS_WITHOUT_QUERY_STRING = [...self::LENIENT_TRAILING_PUNCTUATION_CHARS, '!', ':'];

    // Links aren't rendered as <img>, so there's no query-string concern - `!`/`:`/`}` always strip.
    private const STRICT_TRAILING_PUNCTUATION_CHARS = [...self::LENIENT_TRAILING_PUNCTUATION_CHARS, '!', ':', '}'];

    /**
     * $isTrustedAuthor gates all interactive rendering (link and image alike): a link can phish, and
     * an <img> auto-fetches on every viewer's browser (tracking pixel). When false, url candidates
     * stay part of the surrounding text - not redacted, the raw url is still in the payload, just
     * never rendered as a clickable/fetchable element.
     *
     * $allowImages independently suppresses image rendering for a trusted author (e.g. session
     * topic/location - single-line fields with no room for one, but urls should still linkify).
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
            $lenientUrl = null;

            if ($isHttpScheme) {
                // A trailing `?` can itself be punctuation (e.g. "img.png!?"), so trim known-safe
                // trailing punctuation first and only then check what's left for a genuine `?`.
                $hasQueryString = str_contains(rtrim($rawUrl, implode('', self::LENIENT_TRAILING_PUNCTUATION_CHARS)), '?');
                $imageTrailingPunctuationChars = $hasQueryString
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

            // 'link' only: the frontend's sole consumer, for deciding target=_blank on an <a> -
            // doesn't apply to the <img> an 'image' segment renders as.
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
     * Trims trailing punctuation a human likely typed after a pasted url. A trailing `)` is only
     * trimmed when unbalanced, matching GitHub-flavored Markdown autolink behavior.
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
