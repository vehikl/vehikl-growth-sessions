<?php

namespace Tests\Unit;

use App\Models\Comment;
use Tests\TestCase;

class CommentTest extends TestCase
{
    public function testSegmentsFailsClosedWhenTheCommentHasNoAssociatedUser()
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
