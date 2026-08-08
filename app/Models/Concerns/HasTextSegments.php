<?php

namespace App\Models\Concerns;

use App\Support\TextSegmentParser;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasTextSegments
{
    // No `: Attribute` return type here on purpose: Eloquent's accessor auto-discovery
    // (HasAttributes::getAttributeMarkedMutatorMethods) reflects over every method with an `Attribute`
    // return type and invokes it with zero arguments to see if it's a real accessor. This helper
    // requires arguments, so a typed return would make that scan crash with a missing-argument error.
    // The real accessors (e.g. GrowthSession::topicSegments()) keep the `: Attribute` type - they take
    // no arguments, so Eloquent invoking them is exactly how they're meant to work.
    protected function textSegmentsAttribute(string $text, bool $allowImages = false)
    {
        return Attribute::make(get: fn () => TextSegmentParser::parse($text, $allowImages));
    }
}
