<?php

namespace App\Support;

class CommentContentParser
{
    private const URL_PATTERN = '/https?:\/\/\S+/i';
    private const IMAGE_EXTENSION_PATTERN = '/\.(gif|png|jpe?g|webp)(?:[?#]\S*)?$/i';
    private const TRAILING_PUNCTUATION_PATTERN = '/[.,!?;:)\]]+$/';

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
            $trailingPunctuation = preg_match(self::TRAILING_PUNCTUATION_PATTERN, $rawUrl, $trailingMatch) ? $trailingMatch[0] : '';
            $url = $trailingPunctuation ? substr($rawUrl, 0, -strlen($trailingPunctuation)) : $rawUrl;

            if (!preg_match(self::IMAGE_EXTENSION_PATTERN, $url)) {
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
}
