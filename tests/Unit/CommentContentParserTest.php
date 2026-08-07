<?php

namespace Tests\Unit;

use App\Support\CommentContentParser;
use Tests\TestCase;

class CommentContentParserTest extends TestCase
{
    public function testItReturnsASingleTextSegmentWhenThereIsNoUrl()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => 'just some plain text']],
            CommentContentParser::parse('just some plain text', true)
        );
    }

    public function testItReturnsASingleEmptyTextSegmentForEmptyContent()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => '']],
            CommentContentParser::parse('', true)
        );
    }

    public function testItDoesNotTreatANonImageUrlAsAnImage()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => 'check this out https://example.com/page']],
            CommentContentParser::parse('check this out https://example.com/page', true)
        );
    }

    /** @dataProvider imageExtensionProvider */
    public function testItRendersALoneUrlAsAnImageSegment(string $extension)
    {
        $url = "https://example.com/image.{$extension}";

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public static function imageExtensionProvider(): array
    {
        return [['gif'], ['png'], ['jpg'], ['jpeg'], ['webp']];
    }

    public function testItSplitsSurroundingTextFromAnEmbeddedImageUrl()
    {
        $result = CommentContentParser::parse('look at this https://example.com/funny.gif so good', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'look at this '],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => ' so good'],
        ], $result);
    }

    public function testItHandlesMultipleImageUrlsInTheSameComment()
    {
        $result = CommentContentParser::parse('https://example.com/one.gif and https://example.com/two.png', true);

        $this->assertEquals([
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => ' and '],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
        ], $result);
    }

    public function testItStripsTrailingSentencePunctuationFromTheUrl()
    {
        $result = CommentContentParser::parse('lol check this out https://example.com/funny.gif.', true);

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
            CommentContentParser::parse($url, true)
        );
    }

    public function testItIsCaseInsensitiveOnTheExtension()
    {
        $url = 'https://example.com/funny.GIF';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public function testItIsCaseInsensitiveOnTheUrlScheme()
    {
        $result = CommentContentParser::parse('Https://example.com/funny.gif', true);

        $this->assertEquals([['type' => 'image', 'value' => 'Https://example.com/funny.gif']], $result);
    }

    /** @dataProvider nonImageExtensionProvider */
    public function testItDoesNotTreatANonImageExtensionUrlAsAnImage(string $extension)
    {
        $url = "https://example.com/file.{$extension}";

        $this->assertEquals(
            [['type' => 'text', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public static function nonImageExtensionProvider(): array
    {
        return [['pdf'], ['mp4'], ['exe'], ['svg']];
    }

    public function testItSplitsOutAnImageUrlWrappedInMarkdownStyleParentheses()
    {
        $result = CommentContentParser::parse('link: (https://example.com/funny.gif) neat right?', true);

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
            CommentContentParser::parse($content, false)
        );
    }

    public function testItDoesNotMergeCommaJoinedAdjacentImageUrls()
    {
        $result = CommentContentParser::parse('https://example.com/one.gif,https://example.com/two.png', true);

        $this->assertEquals([
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => ','],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
        ], $result);
    }

    public function testItDoesNotMergeQuotedAdjacentImageUrls()
    {
        $result = CommentContentParser::parse('"https://example.com/one.gif","https://example.com/two.png"', true);

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
        $result = CommentContentParser::parse("https://example.com/one.gif{$separator}https://example.com/two.png", true);

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
            [['type' => 'text', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public function testItKeepsACommaThatIsPartOfTheImageUrlPath()
    {
        $url = 'https://res.cloudinary.com/demo/image/upload/c_scale,w_500/sample.jpg';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public function testItKeepsCommasInsideAQueryStringValue()
    {
        $url = 'https://cdn.example.com/img.png?ids=1,2,3';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public function testItKeepsATrailingExclamationMarkOnASignedCdnUrl()
    {
        $url = 'https://cdn.example.com/img.png?Signature=abc%3D&Expires=1!';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public function testItKeepsAQueryStringEndingInAColon()
    {
        $url = 'https://cdn.example.com/img.png?token=abc:';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            CommentContentParser::parse($url, true)
        );
    }

    public function testItStripsAnUnbalancedTrailingParenthesisEvenWithAQueryString()
    {
        $result = CommentContentParser::parse('(https://cdn.example.com/img.png?token=abc).', true);

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
            CommentContentParser::parse($url, true)
        );
    }
}
