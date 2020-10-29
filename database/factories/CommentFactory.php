<?php

namespace Database\Factories;

use App\Comment;
use App\SocialMob;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => function() {
                return User::factory()->create()->id;
            },
            'social_mob_id' => function() {
                return SocialMob::factory()->create()->id;
            },
            'content' => $this->faker->text
        ];
    }
}
