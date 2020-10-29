<?php

namespace Database\Factories;

use App\SocialMob;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialMobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SocialMob::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'owner_id' => function() {
                return User::factory()->create()->id;
            },
            'title' => $this->faker->sentence(2),
            'topic' => $this->faker->sentence,
            'location' => 'At AnyDesk XYZ - abcdefg',
            'date' => today(),
            'start_time' => now()->setTime(15, 30),
            'end_time' => now()->setTime(17, 00),
            'attendee_limit' => SocialMob::NO_LIMIT,
        ];
    }
}
