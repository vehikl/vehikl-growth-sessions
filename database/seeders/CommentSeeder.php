<?php

namespace Database\Seeders;

use App\Comment;
use App\GrowthSession;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run()
    {
        GrowthSession::all()->each(fn($growthSession) => Comment::factory()
            ->times(random_int(0, 3))
            ->create(['growth_session_id' => $growthSession->id])
        );
    }
}
