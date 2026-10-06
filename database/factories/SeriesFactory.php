<?php

namespace Database\Factories;

use App\Models\Series;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeriesFactory extends Factory
{
    protected $model = Series::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->sentence(2),
            'owner_id' => User::factory()->vehiklMember(),
        ];
    }
}
