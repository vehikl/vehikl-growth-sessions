<?php

namespace Tests\Unit;

use App\Models\Comment;
use Tests\TestCase;

class CommentTest extends TestCase
{
    public function test_segments_fails_closed_when_the_comment_has_no_associated_user()
    {
        $imageUrl = 'https://example.com/funny.gif';
        $comment = new Comment(['content' => $imageUrl]);
        $comment->setRelation('user', null);

        $this->assertEquals(
            [['type' => 'text', 'value' => $imageUrl]],
            $comment->segments
        );
    }
}
