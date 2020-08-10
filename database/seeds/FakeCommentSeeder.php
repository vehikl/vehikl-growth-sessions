<?php

use App\Comment;
use App\SocialMob;
use Illuminate\Database\Seeder;

class FakeCommentSeeder extends Seeder
{
    public function run()
    {
        SocialMob::all()->each(function($socialMob) {
           factory(Comment::class, random_int(0, 3))->create(['social_mob_id' => $socialMob->id]);
        });
    }
}
