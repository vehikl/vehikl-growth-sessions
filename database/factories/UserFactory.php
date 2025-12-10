<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static $defaultPassowrd = '';

    public function vehiklMember(bool $vehiklMember = true): static
    {
        return $this->state([
            'is_vehikl_member' => $vehiklMember
        ]);
    }

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'github_nickname' => $this->faker->userName,
            'avatar' => "https://i.pravatar.cc/50?u={$this->faker->numberBetween()}",
            'password' => self::getDefaultPassword(),
            'remember_token' => Str::random(10),
            'is_vehikl_member' => false,
            'is_visible_in_statistics' => true,
        ];
    }

    public static function getDefaultPassword(): string
    {
        if (!static::$defaultPassowrd) {
            static::$defaultPassowrd = Hash::make('password');
        }

        return static::$defaultPassowrd;
    }
}
