<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Comment;
use App\SocialMob;
use App\User;
use Faker\Generator as Faker;

$factory->define(Comment::class, function (Faker $faker) {
    return [
        'user_id' => function() {
            return User::factory()->create()->id;
        },
        'social_mob_id' => function() {
            return factory(SocialMob::class)->create()->id;
        },
        'content' => $faker->text
    ];
});
