<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(GrowthSessionSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(AnyDesksSeeder::class);
        $this->call(TagSeeder::class);
    }
}
