<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
         $this->call(SocialMobSeeder::class);
         $this->call(CommentSeeder::class);
    }
}
