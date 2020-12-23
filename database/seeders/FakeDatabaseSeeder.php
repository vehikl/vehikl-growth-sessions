<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FakeDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(DatabaseSeeder::class);
        $this->call(FakeGrowthSessionSeeder::class);
        $this->call(FakeCommentSeeder::class);
    }
}
