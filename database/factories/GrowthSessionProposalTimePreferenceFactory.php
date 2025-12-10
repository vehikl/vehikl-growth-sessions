<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GrowthSessionProposalTimePreference>
 */
class GrowthSessionProposalTimePreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'growth_session_proposal_id' => \App\Models\GrowthSessionProposal::factory(),
            'weekday' => fake()->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
            'start_time' => '14:00',
            'end_time' => '17:00',
        ];
    }
}
