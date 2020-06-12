<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\SocialMob;
use App\User;
use Faker\Generator as Faker;

$factory->define(SocialMob::class, function (Faker $faker) {
    return [
        'owner_id' => function() {
            return factory(User::class)->create()->id;
        },
        'topic' => $faker->sentence,
        'location' => 'At AnyDesk XYZ - abcdefg',
        'date' => today(),
        'start_time' => now()->setTime(15, 30),
        'end_time' => now()->setTime(17, 00),
    ];
});
