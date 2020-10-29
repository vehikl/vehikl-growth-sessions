<?php

namespace Database\Factories;

use App\Comment;
use App\SocialMob;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

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
            'user_id' => User::factory(),
            'social_mob_id' => SocialMob::factory(),
            'content' => $this->faker->text
        ];
    }
}
