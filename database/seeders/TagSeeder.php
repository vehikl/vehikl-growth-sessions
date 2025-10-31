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
            ->forEachSequence(
                ['name' => 'PHP'],
                ['name' => 'Laravel'],
                ['name' => 'Vue'],
                ['name' => 'AI'],
                ['name' => 'Frontend'],
                ['name' => 'Backend'],
                ['name' => 'DevOps'],
                ['name' => 'Javascript'],
                ['name' => 'C#'],
                ['name' => 'Ruby'],
                ['name' => 'Rails'],
                ['name' => 'Flask'],
                ['name' => 'NestJS'],
                ['name' => 'Express'],
            )
            ->create();
    }
}
