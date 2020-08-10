<?php

use Illuminate\Database\Seeder;

class FakeDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(DatabaseSeeder::class);
        $this->call(FakeSocialMobSeeder::class);
        $this->call(FakeCommentSeeder::class);
    }
}
