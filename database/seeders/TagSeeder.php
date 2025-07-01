<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\GrowthSession;
use App\Models\Tag;
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
