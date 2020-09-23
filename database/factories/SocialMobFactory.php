<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\SocialMob;
use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(SocialMob::class, function (Faker $faker) {
    return [
        'owner_id' => function() {
            return factory(User::class)->create()->id;
        },
        'title' => Str::limit($faker->sentence, 45),
        'topic' => $faker->sentence,
        'location' => 'At AnyDesk XYZ - abcdefg',
        'date' => today(),
        'start_time' => now()->setTime(15, 30),
        'end_time' => now()->setTime(17, 00),
    ];
});
