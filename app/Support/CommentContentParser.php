<?php

namespace App\Support;

class CommentContentParser
{
    // `(?!https?:\/\/)` stops a match right before a second URL starts, so two URLs joined by any
    // character (or none) don't merge into one broken match. `"'<>|` and backtick are excluded
    // outright since they're never valid unencoded in a URL and commonly wrap or separate a pasted
    // link (quotes, markdown, code spans) — unlike e.g. a comma, which is legal inside a URL's own
    // path or query.
    private const URL_PATTERN = '/https?:\/\/(?:(?!https?:\/\/)[^\s"\'<>`|])+/i';
    private const IMAGE_EXTENSION_PATTERN = '/\.(gif|png|jpe?g|webp)$/i';
    // `!` and `:` are deliberately excluded: unlike the rest of this set they're plausible literal
    // characters in a signed CDN URL's query string, and there's no reliable way to tell that apart
    // from sentence punctuation, so we err on the side of not corrupting the URL.
    private const TRAILING_PUNCTUATION_CHARS = ['.', ',', ';', '?', ']', '"', "'"];

    /**
     * Splits comment text into text/image segments so image & GIF URLs can be rendered as <img>s.
     * $allowImages gates this on the comment author's trust level: rendering a URL as an <img> makes
     * every viewer's browser fetch it, which lets an untrusted poster use it as a tracking pixel.
     *
     * This is the single source of truth for that decision - clients render whatever segments they're
     * given and never see the raw URL for a disallowed image, whether they go through this app's own
     * frontend or hit the API directly.
     */
    public static function parse(string $content, bool $allowImages): array
    {
        if (!$allowImages) {
            return [['type' => 'text', 'value' => $content]];
        }

        $segments = [];
        $lastIndex = 0;

        preg_match_all(self::URL_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$rawUrl, $matchStart]) {
            $url = self::stripTrailingPunctuation($rawUrl);

            if (!self::hasImageExtension($url)) {
                continue;
            }

            if ($matchStart > $lastIndex) {
                $segments[] = ['type' => 'text', 'value' => substr($content, $lastIndex, $matchStart - $lastIndex)];
            }
            $segments[] = ['type' => 'image', 'value' => $url];
            $lastIndex = $matchStart + strlen($url);
        }

        if ($lastIndex < strlen($content) || empty($segments)) {
            $segments[] = ['type' => 'text', 'value' => substr($content, $lastIndex)];
        }

        return $segments;
    }

    /** Whether the URL's path (ignoring query/fragment) ends in a supported image extension. */
    private static function hasImageExtension(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path)) {
            return false;
        }

        return (bool) preg_match(self::IMAGE_EXTENSION_PATTERN, $path);
    }

    /**
     * Trims sentence/wrapping punctuation a human likely typed after a pasted URL. A trailing `)` is
     * only trimmed when it's unbalanced (no matching `(` earlier in the URL), matching how
     * GitHub-flavored Markdown autolinks handle "(see http://example.com/x.png)".
     */
    private static function stripTrailingPunctuation(string $url): string
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
            } elseif (!in_array($char, self::TRAILING_PUNCTUATION_CHARS, true)) {
                break;
            }

            $end--;
        }

        return substr($url, 0, $end);
    }
}
