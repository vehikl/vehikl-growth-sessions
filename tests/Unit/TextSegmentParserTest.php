<?php

namespace Tests\Unit;

use App\Support\TextSegmentParser;
use Tests\TestCase;

class TextSegmentParserTest extends TestCase
{
    public function test_it_returns_a_single_text_segment_when_there_is_no_url()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => 'just some plain text']],
            TextSegmentParser::parse('just some plain text', true)
        );
    }

    public function test_it_returns_a_single_empty_text_segment_for_empty_content()
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => '']],
            TextSegmentParser::parse('', true)
        );
    }

    public function test_it_does_not_treat_a_non_image_url_as_an_image()
    {
        $this->assertEquals([
            ['type' => 'text', 'value' => 'check this out '],
            ['type' => 'link', 'value' => 'https://example.com/page', 'opens_in_new_tab' => true],
        ], TextSegmentParser::parse('check this out https://example.com/page', true));
    }

    /** @dataProvider imageExtensionProvider */
    public function test_it_renders_a_lone_url_as_an_image_segment(string $extension)
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

    public function test_it_splits_surrounding_text_from_an_embedded_image_url()
    {
        $result = TextSegmentParser::parse('look at this https://example.com/funny.gif so good', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'look at this '],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => ' so good'],
        ], $result);
    }

    public function test_it_handles_multiple_image_urls_in_the_same_comment()
    {
        $result = TextSegmentParser::parse('https://example.com/one.gif and https://example.com/two.png', true);

        $this->assertEquals([
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => ' and '],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
        ], $result);
    }

    public function test_it_strips_trailing_sentence_punctuation_from_the_url()
    {
        $result = TextSegmentParser::parse('lol check this out https://example.com/funny.gif.', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'lol check this out '],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => '.'],
        ], $result);
    }

    public function test_it_keeps_a_query_string_that_is_part_of_the_image_url()
    {
        $url = 'https://example.com/funny.gif?cid=123';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_is_case_insensitive_on_the_extension()
    {
        $url = 'https://example.com/funny.GIF';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_is_case_insensitive_on_the_url_scheme()
    {
        $result = TextSegmentParser::parse('Https://example.com/funny.gif', true);

        $this->assertEquals([['type' => 'image', 'value' => 'Https://example.com/funny.gif']], $result);
    }

    public function test_opens_in_new_tab_is_case_insensitive_on_the_url_scheme()
    {
        $result = TextSegmentParser::parse('HTTP://EXAMPLE.COM', true);

        $this->assertEquals([['type' => 'link', 'value' => 'HTTP://EXAMPLE.COM', 'opens_in_new_tab' => true]], $result);
    }

    /** @dataProvider nonImageExtensionProvider */
    public function test_it_does_not_treat_a_non_image_extension_url_as_an_image(string $extension)
    {
        $url = "https://example.com/file.{$extension}";

        $this->assertEquals(
            [['type' => 'link', 'value' => $url, 'opens_in_new_tab' => true]],
            TextSegmentParser::parse($url, true)
        );
    }

    public static function nonImageExtensionProvider(): array
    {
        return [['pdf'], ['mp4'], ['exe'], ['svg']];
    }

    public function test_it_splits_out_an_image_url_wrapped_in_markdown_style_parentheses()
    {
        $result = TextSegmentParser::parse('link: (https://example.com/funny.gif) neat right?', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'link: ('],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => ') neat right?'],
        ], $result);
    }

    public function test_it_keeps_an_image_url_as_plain_text_when_images_are_not_allowed_even_for_a_trusted_author()
    {
        $content = 'look at this https://example.com/funny.gif so good';

        $this->assertEquals(
            [['type' => 'text', 'value' => $content]],
            TextSegmentParser::parse($content, isTrustedAuthor: true, allowImages: false)
        );
    }

    public function test_it_does_not_merge_comma_joined_adjacent_image_urls()
    {
        $result = TextSegmentParser::parse('https://example.com/one.gif,https://example.com/two.png', true);

        $this->assertEquals([
            ['type' => 'image', 'value' => 'https://example.com/one.gif'],
            ['type' => 'text', 'value' => ','],
            ['type' => 'image', 'value' => 'https://example.com/two.png'],
        ], $result);
    }

    public function test_it_does_not_merge_quoted_adjacent_image_urls()
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
    public function test_it_does_not_merge_adjacent_image_urls_joined_by_other_separators(string $separator)
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

    public function test_it_does_not_treat_an_image_extension_inside_a_query_value_as_an_image_url()
    {
        $url = 'https://example.com/download?file=report.png';

        $this->assertEquals(
            [['type' => 'link', 'value' => $url, 'opens_in_new_tab' => true]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_keeps_a_comma_that_is_part_of_the_image_url_path()
    {
        $url = 'https://res.cloudinary.com/demo/image/upload/c_scale,w_500/sample.jpg';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_keeps_commas_inside_a_query_string_value()
    {
        $url = 'https://cdn.example.com/img.png?ids=1,2,3';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_keeps_a_trailing_exclamation_mark_on_a_signed_cdn_url()
    {
        $url = 'https://cdn.example.com/img.png?Signature=abc%3D&Expires=1!';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_keeps_a_query_string_ending_in_a_colon()
    {
        $url = 'https://cdn.example.com/img.png?token=abc:';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_strips_an_unbalanced_trailing_parenthesis_even_with_a_query_string()
    {
        $result = TextSegmentParser::parse('(https://cdn.example.com/img.png?token=abc).', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => '('],
            ['type' => 'image', 'value' => 'https://cdn.example.com/img.png?token=abc'],
            ['type' => 'text', 'value' => ').'],
        ], $result);
    }

    public function test_it_keeps_a_trailing_closing_parenthesis_balanced_by_an_earlier_one()
    {
        $url = 'https://example.com/img.png?callback=foo(bar)';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_keeps_a_trailing_exclamation_mark_after_a_real_query_string_even_with_a_leading_question_mark_boundary()
    {
        $url = 'https://example.com/funny.gif?cid=3!';

        $this->assertEquals(
            [['type' => 'image', 'value' => $url]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_a_trailing_exclamation_then_question_mark_is_treated_as_sentence_punctuation_not_a_query_string()
    {
        $result = TextSegmentParser::parse('nice https://example.com/img.png!?', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'nice '],
            ['type' => 'image', 'value' => 'https://example.com/img.png'],
            ['type' => 'text', 'value' => '!?'],
        ], $result);
    }

    public function test_it_strips_an_extra_unbalanced_trailing_parenthesis_but_keeps_the_inner_balanced_pair()
    {
        $result = TextSegmentParser::parse('https://example.com/foo(bar))', true);

        $this->assertEquals([
            ['type' => 'link', 'value' => 'https://example.com/foo(bar)', 'opens_in_new_tab' => true],
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
    public function test_lenient_trailing_punctuation_trimming_on_images(string $description, string $input, string $expected)
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
            'a trailing exclamation mark is stripped when there is no query string to protect' => [
                'a trailing exclamation mark is stripped when there is no query string to protect',
                'https://example.com/img.png!',
                'https://example.com/img.png',
            ],
            'a trailing colon is stripped when there is no query string to protect' => [
                'a trailing colon is stripped when there is no query string to protect',
                'https://example.com/img.png:',
                'https://example.com/img.png',
            ],
        ];
    }

    /** @dataProvider strictTrailingPunctuationProvider */
    public function test_strict_trailing_punctuation_trimming_on_links(string $description, string $input, string $expected)
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

    public function test_it_renders_a_plain_url_as_a_link_segment()
    {
        $result = TextSegmentParser::parse('hello https://example.com world', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'hello '],
            ['type' => 'link', 'value' => 'https://example.com', 'opens_in_new_tab' => true],
            ['type' => 'text', 'value' => ' world'],
        ], $result);
    }

    public function test_it_keeps_an_apostrophe_inside_a_url_path()
    {
        $url = "https://en.wikipedia.org/wiki/Murphy's_Law";

        $this->assertEquals(
            [['type' => 'link', 'value' => $url, 'opens_in_new_tab' => true]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_it_still_strips_a_wrapping_apostrophe_around_a_url()
    {
        $result = TextSegmentParser::parse("check 'https://example.com/page' out", true);

        $this->assertEquals([
            ['type' => 'text', 'value' => "check '"],
            ['type' => 'link', 'value' => 'https://example.com/page', 'opens_in_new_tab' => true],
            ['type' => 'text', 'value' => "' out"],
        ], $result);
    }

    public function test_it_renders_a_mailto_url_as_a_link_segment()
    {
        $result = TextSegmentParser::parse('email me at mailto:jane@example.com please', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'email me at '],
            ['type' => 'link', 'value' => 'mailto:jane@example.com', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => ' please'],
        ], $result);
    }

    public function test_it_renders_a_tel_url_as_a_link_segment()
    {
        $result = TextSegmentParser::parse('call tel:5551234 now', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'call '],
            ['type' => 'link', 'value' => 'tel:5551234', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => ' now'],
        ], $result);
    }

    public function test_image_extension_detection_is_scoped_to_http_schemes_only()
    {
        // hasImageExtension() only checks the string's *path*, so a mailto:/tel: candidate whose
        // local part happens to end in an image extension must not be misclassified as an image -
        // image rendering is an http(s)-only concept, and mailto:/tel: values are never fetched as
        // <img src>. Regression test for a bug where tel:funny.gif rendered as `type: image`.
        $telResult = TextSegmentParser::parse('call tel:funny.gif now', true);
        $mailtoResult = TextSegmentParser::parse('email mailto:funny.gif now', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'call '],
            ['type' => 'link', 'value' => 'tel:funny.gif', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => ' now'],
        ], $telResult);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'email '],
            ['type' => 'link', 'value' => 'mailto:funny.gif', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => ' now'],
        ], $mailtoResult);
    }

    public function test_it_renders_a_mix_of_an_image_and_a_link_in_the_same_content()
    {
        $result = TextSegmentParser::parse(
            'photo https://example.com/funny.gif and info https://example.com/info',
            true
        );

        $this->assertEquals([
            ['type' => 'text', 'value' => 'photo '],
            ['type' => 'image', 'value' => 'https://example.com/funny.gif'],
            ['type' => 'text', 'value' => ' and info '],
            ['type' => 'link', 'value' => 'https://example.com/info', 'opens_in_new_tab' => true],
        ], $result);
    }

    public function test_an_untrusted_authors_image_and_plain_link_both_stay_inert_text()
    {
        $content = 'photo https://example.com/funny.gif and info https://example.com/info';

        $this->assertEquals(
            [['type' => 'text', 'value' => $content]],
            TextSegmentParser::parse($content, isTrustedAuthor: false)
        );
    }

    /** @dataProvider untrustedAuthorSchemeProvider */
    public function test_an_untrusted_authors_url_of_any_recognized_scheme_stays_inert_text(string $content)
    {
        $this->assertEquals(
            [['type' => 'text', 'value' => $content]],
            TextSegmentParser::parse($content, isTrustedAuthor: false)
        );
    }

    public static function untrustedAuthorSchemeProvider(): array
    {
        return [
            'https link' => ['check this out https://example.com/page'],
            'mailto link' => ['email me at mailto:jane@example.com please'],
            'tel link' => ['call tel:5551234 now'],
        ];
    }

    public function test_a_bare_http_substring_inside_a_urls_query_or_path_does_not_truncate_the_match()
    {
        $queryResult = TextSegmentParser::parse('https://example.com/?q=http:test', true);
        $pathResult = TextSegmentParser::parse('https://example.com/wiki/HTTP:_header', true);

        $this->assertEquals([['type' => 'link', 'value' => 'https://example.com/?q=http:test', 'opens_in_new_tab' => true]], $queryResult);
        $this->assertEquals([['type' => 'link', 'value' => 'https://example.com/wiki/HTTP:_header', 'opens_in_new_tab' => true]], $pathResult);
    }

    public function test_a_nested_url_inside_a_query_parameter_value_does_not_split_the_outer_url()
    {
        $url = 'https://example.com/login?redirect=https://app.example.com/callback';

        $this->assertEquals(
            [['type' => 'link', 'value' => $url, 'opens_in_new_tab' => true]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_a_tel_or_mailto_looking_path_segment_inside_an_https_url_does_not_split_the_url()
    {
        $telUrl = 'https://example.com/contact/tel:5551234';
        $mailtoUrl = 'https://example.com/contact/mailto:foo';

        $this->assertEquals(
            [['type' => 'link', 'value' => $telUrl, 'opens_in_new_tab' => true]],
            TextSegmentParser::parse($telUrl, true)
        );
        $this->assertEquals(
            [['type' => 'link', 'value' => $mailtoUrl, 'opens_in_new_tab' => true]],
            TextSegmentParser::parse($mailtoUrl, true)
        );
    }

    public function test_a_non_breaking_space_bounds_a_url_the_same_as_a_regular_space()
    {
        $result = TextSegmentParser::parse("https://example.com/foo\u{00A0}bar", true);

        $this->assertEquals([
            ['type' => 'link', 'value' => 'https://example.com/foo', 'opens_in_new_tab' => true],
            ['type' => 'text', 'value' => "\u{00A0}bar"],
        ], $result);
    }

    public function test_it_does_not_recognize_unsupported_protocols()
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

    public function test_trimming_that_eats_into_the_schemes_own_delimiter_skips_the_candidate_entirely()
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

    public function test_a_disallowed_image_produces_one_continuous_text_segment_for_the_whole_input()
    {
        $content = 'hello https://foo.com/image.png world';

        $this->assertEquals(
            [['type' => 'text', 'value' => $content]],
            TextSegmentParser::parse($content, isTrustedAuthor: true, allowImages: false)
        );
    }

    public function test_a_disallowed_image_followed_by_a_link_still_extracts_the_link_for_a_trusted_author()
    {
        $content = 'hello https://foo.com/image.png world https://example.com';

        $this->assertEquals([
            ['type' => 'text', 'value' => 'hello https://foo.com/image.png world '],
            ['type' => 'link', 'value' => 'https://example.com', 'opens_in_new_tab' => true],
        ], TextSegmentParser::parse($content, isTrustedAuthor: true, allowImages: false));
    }

    public function test_links_on_both_sides_of_a_disallowed_image_are_both_extracted_for_a_trusted_author()
    {
        $content = 'https://a.com https://image.com/a.png https://b.com';

        $this->assertEquals([
            ['type' => 'link', 'value' => 'https://a.com', 'opens_in_new_tab' => true],
            ['type' => 'text', 'value' => ' https://image.com/a.png '],
            ['type' => 'link', 'value' => 'https://b.com', 'opens_in_new_tab' => true],
        ], TextSegmentParser::parse($content, isTrustedAuthor: true, allowImages: false));
    }

    /** @dataProvider adjacentSchemePairProvider */
    public function test_adjacent_urls_of_different_schemes_with_no_separator_each_split_into_their_own_segment(
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
                    ['type' => 'link', 'value' => 'https://a.com', 'opens_in_new_tab' => true],
                    ['type' => 'link', 'value' => 'mailto:b@c.com', 'opens_in_new_tab' => false],
                ],
            ],
            'mailto then tel' => [
                'mailto:a@b.comtel:123',
                [
                    ['type' => 'link', 'value' => 'mailto:a@b.com', 'opens_in_new_tab' => false],
                    ['type' => 'link', 'value' => 'tel:123', 'opens_in_new_tab' => false],
                ],
            ],
            'tel then https' => [
                'tel:123https://a.com',
                [
                    ['type' => 'link', 'value' => 'tel:123', 'opens_in_new_tab' => false],
                    ['type' => 'link', 'value' => 'https://a.com', 'opens_in_new_tab' => true],
                ],
            ],
        ];
    }

    public function test_a_sentence_containing_all_three_schemes_splits_each_into_its_own_segment()
    {
        $result = TextSegmentParser::parse(
            'web https://example.com email mailto:jane@example.com phone tel:5551234 done',
            true
        );

        $this->assertEquals([
            ['type' => 'text', 'value' => 'web '],
            ['type' => 'link', 'value' => 'https://example.com', 'opens_in_new_tab' => true],
            ['type' => 'text', 'value' => ' email '],
            ['type' => 'link', 'value' => 'mailto:jane@example.com', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => ' phone '],
            ['type' => 'link', 'value' => 'tel:5551234', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => ' done'],
        ], $result);
    }

    public function test_a_mailto_query_string_survives()
    {
        $url = 'mailto:a@b.com?subject=Hello';

        $this->assertEquals(
            [['type' => 'link', 'value' => $url, 'opens_in_new_tab' => false]],
            TextSegmentParser::parse($url, true)
        );
    }

    public function test_the_strict_trim_policy_strips_a_trailing_exclamation_mark_from_any_link_scheme_including_mailto()
    {
        $result = TextSegmentParser::parse('mailto:a@b.com?subject=Hello!', true);

        $this->assertEquals([
            ['type' => 'link', 'value' => 'mailto:a@b.com?subject=Hello', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => '!'],
        ], $result);
    }

    public function test_it_pins_the_accepted_mid_word_false_positive_trade_off()
    {
        $result = TextSegmentParser::parse('Hotel:5-star downtown', true);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'Ho'],
            ['type' => 'link', 'value' => 'tel:5-star', 'opens_in_new_tab' => false],
            ['type' => 'text', 'value' => ' downtown'],
        ], $result);
    }
}
