<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AnyDeskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'remote_desk_id' => $this->faker->numerify('### ### ###')
        ];
    }
}
