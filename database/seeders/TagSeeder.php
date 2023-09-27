<?php

namespace Database\Seeders;

use App\Comment;
use App\GrowthSession;
use App\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run()
    {
        Tag::factory()
            ->count(10)
            ->create();
    }
}
