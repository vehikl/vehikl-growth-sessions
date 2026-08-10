<?php

namespace Database\Factories;

use App\Models\GrowthSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GrowthSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GrowthSession::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(2),
            'topic' => $this->faker->sentence,
            'location' => 'At AnyDesk XYZ - abcdefg',
            'date' => function () {
                if (today()->isWeekend()) {
                    return Carbon::parse('Next Wednesday');
                }
                return today();
            },
            'start_time' => now()->setTime(15, 30),
            'end_time' => now()->setTime(17, 00),
            'attendee_limit' => GrowthSession::NO_LIMIT,
            'is_public' => TRUE
        ];
    }

    /**
     * A growth session that stays out of every listing but is viewable by anyone holding its invite link.
     */
    public function unlisted(): static
    {
        return $this->state(fn() => [
            'is_public' => false,
            'share_token' => Str::random(40),
        ]);
    }
}
