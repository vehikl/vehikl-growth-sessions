<?php

namespace Database\Factories;

use App\Email;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailFactory extends Factory
{
    protected $model = Email::class;

    public function definition(): array
    {
        return [
            'address' => $this->faker->unique()->safeEmail(),
            'user_id' => User::factory(),
        ];
    }
}
