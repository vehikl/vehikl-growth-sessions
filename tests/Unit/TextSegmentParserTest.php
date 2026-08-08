<?php

namespace Tests\Unit;

use App\Support\TextSegmentParser;
use Tests\TestCase;

class TextSegmentParserTest extends TestCase
{
    public function testItReturnsASingleTextSegmentWhenThereIsNoUrl()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => 'just some plain text']],
            TextSegmentParser::parse('just some plain text', true)
        );
    }

    public function testItReturnsASingleEmptyTextSegmentForEmptyContent()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => '']],
            TextSegmentParser::parse('', true)
        );
    }

    public function testItDoesNotTreatANonImageUrlAsAnImage()
    {
        $this->assertEquals([
            ['type' => 'text', 'value' => 'check this out '],
            ['type' => 'link', 'value' => 'https://example.com/page'],
        ], TextSegmentParser::parse('check this out https://example.com/page', true));
    }

    /** @dataProvider imageExtensionProvider */
    public function testItRendersALoneUrlAsAnImageSegment(string $extension)
    {
        $url = "https://example.com/image.{$extension}";

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public static function imageExtensionProvider(): array
    {
        return [['gif'], ['png'], ['jpg'], ['jpeg'], ['webp']];
    }

    public function testItSplitsSurroundingTextFromAnEmbeddedImageUrl()
    {
        $result = TextSegmentParser::parse('look at this https://example.com/funny.gif so good', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'look at this '],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => ' so good'],
        ], $result);
    }

    public function testItHandlesMultipleImageUrlsInTheSameComment()
    {
        $result = TextSegmentParser::parse('https://example.com/one.gif and https://example.com/two.png', true);

        $this->assertEquals([
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => ' and '],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
        ], $result);
    }

    public function testItStripsTrailingSentencePunctuationFromTheUrl()
    {
        $result = TextSegmentParser::parse('lol check this out https://example.com/funny.gif.', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'lol check this out '],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => '.'],
        ], $result);
    }

    public function testItKeepsAQueryStringThatIsPartOfTheImageUrl()
    {
        $url = 'https://example.com/funny.gif?cid=123';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItIsCaseInsensitiveOnTheExtension()
    {
        $url = 'https://example.com/funny.GIF';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItIsCaseInsensitiveOnTheUrlScheme()
    {
        $result = TextSegmentParser::parse('Https://example.com/funny.gif', true);

        $this->assertEquals([['type' => 'image', 'value' => 'Https://example.com/funny.gif']], $result);
    }

    /** @dataProvider nonImageExtensionProvider */
    public function testItDoesNotTreatANonImageExtensionUrlAsAnImage(string $extension)
    {
        $url = "https://example.com/file.{$extension}";

        $this->assertEquals(
            [['type' => 'link', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public static function nonImageExtensionProvider(): array
    {
        return [['pdf'], ['mp4'], ['exe'], ['svg']];
    }

    public function testItSplitsOutAnImageUrlWrappedInMarkdownStyleParentheses()
    {
        $result = TextSegmentParser::parse('link: (https://example.com/funny.gif) neat right?', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'link: ('],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => ') neat right?'],
        ], $result);
    }

    public function testItKeepsAnImageUrlAsPlainTextWhenImagesAreNotAllowed()
    {
        $content = 'look at this https://example.com/funny.gif so good';

        $this->assertEquals(
            [['type' => 'text', 'value' => $content]],
            TextSegmentParser::parse($content, false)
        );
    }

    public function testItDoesNotMergeCommaJoinedAdjacentImageUrls()
    {
        $result = TextSegmentParser::parse('https://example.com/one.gif,https://example.com/two.png', true);

        $this->assertEquals([
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => ','],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
        ], $result);
    }

    public function testItDoesNotMergeQuotedAdjacentImageUrls()
    {
        $result = TextSegmentParser::parse('"https://example.com/one.gif","https://example.com/two.png"', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => '"'],
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => '","'],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
            ['type' => 'text', 'value' => '"'],
        ], $result);
    }

    /** @dataProvider separatorProvider */
    public function testItDoesNotMergeAdjacentImageUrlsJoinedByOtherSeparators(string $separator)
    {
        $result = TextSegmentParser::parse("https://example.com/one.gif{$separator}https://example.com/two.png", true);

        $this->assertEquals([
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => $separator],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
        ], $result);
    }

    public static function separatorProvider(): array
    {
        return [[';'], ['|']];
    }

    public function testItDoesNotTreatAnImageExtensionInsideAQueryValueAsAnImageUrl()
    {
        $url = 'https://example.com/download?file=report.png';

        $this->assertEquals(
            [['type' => 'link', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItKeepsACommaThatIsPartOfTheImageUrlPath()
    {
        $url = 'https://res.cloudinary.com/demo/image/upload/c_scale,w_500/sample.jpg';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItKeepsCommasInsideAQueryStringValue()
    {
        $url = 'https://cdn.example.com/img.png?ids=1,2,3';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItKeepsATrailingExclamationMarkOnASignedCdnUrl()
    {
        $url = 'https://cdn.example.com/img.png?Signature=abc%3D&Expires=1!';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItKeepsAQueryStringEndingInAColon()
    {
        $url = 'https://cdn.example.com/img.png?token=abc:';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItStripsAnUnbalancedTrailingParenthesisEvenWithAQueryString()
    {
        $result = TextSegmentParser::parse('(https://cdn.example.com/img.png?token=abc).', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => '('],
            ['type' => 'image', 'value' => 'https://cdn.example.com/img.png?token=abc'],
            ['type' => 'text', 'value' => ').'],
        ], $result);
    }

    public function testItKeepsATrailingClosingParenthesisBalancedByAnEarlierOne()
    {
        $url = 'https://example.com/img.png?callback=foo(bar)';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testItStripsAnExtraUnbalancedTrailingParenthesisButKeepsTheInnerBalancedPair()
    {
        $result = TextSegmentParser::parse('https://example.com/foo(bar))', true);

        $this->assertEquals([
            ['type' => 'link', 'value' => 'https://example.com/foo(bar)'],
            ['type' => 'text', 'value' => ')'],
        ], $result);
    }

    /**
     * These cases used to be shared with the frontend via tests/fixtures/UrlTrailingPunctuation.json
     * and linkify.spec.ts. Now that this parser is the single implementation, they're folded directly
     * into this suite instead - one provider per trim policy.
     *
     * @dataProvider lenientTrailingPunctuationProvider
     */
    public function testLenientTrailingPunctuationTrimmingOnImages(string $description, string $input, string $expected)
    {
        $imageSegment = collect(TextSegmentParser::parse($input, true))->firstWhere('type', 'image');

        $this->assertNotNull($imageSegment, "$description: no image segment found");
        $this->assertEquals($expected, $imageSegment['value'], $description);
    }

    public static function lenientTrailingPunctuationProvider(): array
    {
        return [
            'trailing period is stripped' => [
                'trailing period is stripped', 'https://example.com/img.png.', 'https://example.com/img.png',
            ],
            'trailing comma is stripped' => [
                'trailing comma is stripped', 'https://example.com/img.png,', 'https://example.com/img.png',
            ],
            'trailing closing bracket is stripped' => [
                'trailing closing bracket is stripped', 'https://example.com/img.png]', 'https://example.com/img.png',
            ],
            'trailing double quote is stripped' => [
                'trailing double quote is stripped', 'https://example.com/img.png"', 'https://example.com/img.png',
            ],
            'an unbalanced trailing parenthesis is stripped' => [
                'an unbalanced trailing parenthesis is stripped', '(https://example.com/img.png)', 'https://example.com/img.png',
            ],
            'a trailing exclamation mark is kept (signed CDN query character)' => [
                'a trailing exclamation mark is kept',
                'https://cdn.example.com/img.png?Signature=abc%3D&Expires=1!',
                'https://cdn.example.com/img.png?Signature=abc%3D&Expires=1!',
            ],
            'a trailing colon is kept (signed CDN query character)' => [
                'a trailing colon is kept',
                'https://cdn.example.com/img.png?token=abc:',
                'https://cdn.example.com/img.png?token=abc:',
            ],
            'a trailing closing brace is kept (no lenient rule for it)' => [
                'a trailing closing brace is kept',
                'https://cdn.example.com/img.png?token={abc}',
                'https://cdn.example.com/img.png?token={abc}',
            ],
            'a balanced trailing parenthesis inside the query is kept' => [
                'a balanced trailing parenthesis inside the query is kept',
                'https://example.com/img.png?callback=foo(bar)',
                'https://example.com/img.png?callback=foo(bar)',
            ],
        ];
    }

    /** @dataProvider strictTrailingPunctuationProvider */
    public function testStrictTrailingPunctuationTrimmingOnLinks(string $description, string $input, string $expected)
    {
        $linkSegment = collect(TextSegmentParser::parse($input, true))->firstWhere('type', 'link');

        $this->assertNotNull($linkSegment, "$description: no link segment found");
        $this->assertEquals($expected, $linkSegment['value'], $description);
    }

    public static function strictTrailingPunctuationProvider(): array
    {
        return [
            'trailing period is stripped' => [
                'trailing period is stripped', 'https://example.com/page.', 'https://example.com/page',
            ],
            'trailing comma is stripped' => [
                'trailing comma is stripped', 'https://example.com/page,', 'https://example.com/page',
            ],
            'trailing closing bracket is stripped' => [
                'trailing closing bracket is stripped', 'https://example.com/page]', 'https://example.com/page',
            ],
            'trailing double quote is stripped' => [
                'trailing double quote is stripped', 'https://example.com/page"', 'https://example.com/page',
            ],
            'trailing exclamation mark is stripped (unlike images)' => [
                'trailing exclamation mark is stripped', 'https://example.com/page!', 'https://example.com/page',
            ],
            'trailing colon is stripped (unlike images)' => [
                'trailing colon is stripped', 'https://example.com/page:', 'https://example.com/page',
            ],
            'trailing closing brace is stripped (unlike images)' => [
                'trailing closing brace is stripped', 'https://example.com/page}', 'https://example.com/page',
            ],
            'an unbalanced trailing parenthesis is stripped' => [
                'an unbalanced trailing parenthesis is stripped', '(https://example.com/page)', 'https://example.com/page',
            ],
            'a balanced trailing parenthesis is kept' => [
                'a balanced trailing parenthesis is kept', 'https://example.com/page(1)', 'https://example.com/page(1)',
            ],
        ];
    }

    // --- Link segments ---------------------------------------------------

    public function testItRendersAPlainUrlAsALinkSegment()
    {
        $result = TextSegmentParser::parse('hello https://example.com world', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'hello '],
            ['type' => 'link', 'value' => 'https://example.com'],
            ['type' => 'text', 'value' => ' world'],
        ], $result);
    }

    public function testItRendersAMailtoUrlAsALinkSegment()
    {
        $result = TextSegmentParser::parse('email me at mailto:jane@example.com please', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'email me at '],
            ['type' => 'link', 'value' => 'mailto:jane@example.com'],
            ['type' => 'text', 'value' => ' please'],
        ], $result);
    }

    public function testItRendersATelUrlAsALinkSegment()
    {
        $result = TextSegmentParser::parse('call tel:5551234 now', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'call '],
            ['type' => 'link', 'value' => 'tel:5551234'],
            ['type' => 'text', 'value' => ' now'],
        ], $result);
    }

    public function testImageExtensionDetectionIsScopedToHttpSchemesOnly()
    {
        // hasImageExtension() only checks the string's *path*, so a mailto:/tel: candidate whose
        // local part happens to end in an image extension must not be misclassified as an image -
        // image rendering is an http(s)-only concept, and mailto:/tel: values are never fetched as
        // <img src>. Regression test for a bug where tel:funny.gif rendered as `type: image`.
        $telResult = TextSegmentParser::parse('call tel:funny.gif now', true);
        $mailtoResult = TextSegmentParser::parse('email mailto:funny.gif now', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'call '],
            ['type' => 'link', 'value' => 'tel:funny.gif'],
            ['type' => 'text', 'value' => ' now'],
        ], $telResult);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'email '],
            ['type' => 'link', 'value' => 'mailto:funny.gif'],
            ['type' => 'text', 'value' => ' now'],
        ], $mailtoResult);
    }

    public function testItRendersAMixOfAnImageAndALinkInTheSameContent()
    {
        $result = TextSegmentParser::parse(
            'photo https://example.com/funny.gif and info https://example.com/info',
            true
        );

        $this->assertEquals([
            ['type' => 'text', 'value' => 'photo '],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => ' and info '],
            ['type' => 'link', 'value' => 'https://example.com/info'],
        ], $result);
    }

    public function testAnUntrustedAuthorsImageStaysTextWhileTheirPlainLinkStillBecomesALink()
    {
        $result = TextSegmentParser::parse(
            'photo https://example.com/funny.gif and info https://example.com/info',
            false
        );

        $this->assertEquals([
            ['type' => 'text', 'value' => 'photo https://example.com/funny.gif and info '],
            ['type' => 'link', 'value' => 'https://example.com/info'],
        ], $result);
    }

    public function testABareHttpSubstringInsideAUrlsQueryOrPathDoesNotTruncateTheMatch()
    {
        $queryResult = TextSegmentParser::parse('https://example.com/?q=http:test', true);
        $pathResult = TextSegmentParser::parse('https://example.com/wiki/HTTP:_header', true);

        $this->assertEquals([['type' => 'link', 'value' => 'https://example.com/?q=http:test']], $queryResult);
        $this->assertEquals([['type' => 'link', 'value' => 'https://example.com/wiki/HTTP:_header']], $pathResult);
    }

    public function testItDoesNotRecognizeUnsupportedProtocols()
    {
        $cases = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'ftp://example.com',
        ];

        foreach ($cases as $input) {
            $this->assertEquals(
                [['type' => 'text', 'value' => $input]],
                TextSegmentParser::parse($input, true),
                $input
            );
        }
    }

    public function testTrimmingThatEatsIntoTheSchemesOwnDelimiterSkipsTheCandidateEntirely()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => 'tel:.']],
            TextSegmentParser::parse('tel:.', true)
        );

        $this->assertEquals(
            [['type' => 'text', 'value' => 'mailto:!']],
            TextSegmentParser::parse('mailto:!', true)
        );
    }

    public function testADisallowedImageProducesOneContinuousTextSegmentForTheWholeInput()
    {
        $content = 'hello https://foo.com/image.png world';

        $this->assertEquals(
            [['type' => 'text', 'value' => $content]],
            TextSegmentParser::parse($content, false)
        );
    }

    public function testADisallowedImageFollowedByALinkStillExtractsTheLink()
    {
        $content = 'hello https://foo.com/image.png world https://example.com';

        $this->assertEquals([
            ['type' => 'text', 'value' => 'hello https://foo.com/image.png world '],
            ['type' => 'link', 'value' => 'https://example.com'],
        ], TextSegmentParser::parse($content, false));
    }

    public function testLinksOnBothSidesOfADisallowedImageAreBothExtracted()
    {
        $content = 'https://a.com https://image.com/a.png https://b.com';

        $this->assertEquals([
            ['type' => 'link', 'value' => 'https://a.com'],
            ['type' => 'text', 'value' => ' https://image.com/a.png '],
            ['type' => 'link', 'value' => 'https://b.com'],
        ], TextSegmentParser::parse($content, false));
    }

    /** @dataProvider adjacentSchemePairProvider */
    public function testAdjacentUrlsOfDifferentSchemesWithNoSeparatorEachSplitIntoTheirOwnSegment(
        string $input,
        array $expected
    ) {
        $this->assertEquals($expected, TextSegmentParser::parse($input, true));
    }

    public static function adjacentSchemePairProvider(): array
    {
        return [
            'https then mailto' => [
                'https://a.commailto:b@c.com',
                [
                    ['type' => 'link', 'value' => 'https://a.com'],
                    ['type' => 'link', 'value' => 'mailto:b@c.com'],
                ],
            ],
            'mailto then tel' => [
                'mailto:a@b.comtel:123',
                [
                    ['type' => 'link', 'value' => 'mailto:a@b.com'],
                    ['type' => 'link', 'value' => 'tel:123'],
                ],
            ],
            'tel then https' => [
                'tel:123https://a.com',
                [
                    ['type' => 'link', 'value' => 'tel:123'],
                    ['type' => 'link', 'value' => 'https://a.com'],
                ],
            ],
        ];
    }

    public function testASentenceContainingAllThreeSchemesSplitsEachIntoItsOwnSegment()
    {
        $result = TextSegmentParser::parse(
            'web https://example.com email mailto:jane@example.com phone tel:5551234 done',
            true
        );

        $this->assertEquals([
            ['type' => 'text', 'value' => 'web '],
            ['type' => 'link', 'value' => 'https://example.com'],
            ['type' => 'text', 'value' => ' email '],
            ['type' => 'link', 'value' => 'mailto:jane@example.com'],
            ['type' => 'text', 'value' => ' phone '],
            ['type' => 'link', 'value' => 'tel:5551234'],
            ['type' => 'text', 'value' => ' done'],
        ], $result);
    }

    public function testAMailtoQueryStringSurvives()
    {
        $url = 'mailto:a@b.com?subject=Hello';

        $this->assertEquals(
            [['type' => 'link', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function testTheStrictTrimPolicyStripsATrailingExclamationMarkFromAnyLinkSchemeIncludingMailto()
    {
        $result = TextSegmentParser::parse('mailto:a@b.com?subject=Hello!', true);

        $this->assertEquals([
            ['type' => 'link', 'value' => 'mailto:a@b.com?subject=Hello'],
            ['type' => 'text', 'value' => '!'],
        ], $result);
    }

    public function testItPinsTheAcceptedMidWordFalsePositiveTradeOff()
    {
        $result = TextSegmentParser::parse('Hotel:5-star downtown', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'Ho'],
            ['type' => 'link', 'value' => 'tel:5-star'],
            ['type' => 'text', 'value' => ' downtown'],
        ], $result);
    }
}
