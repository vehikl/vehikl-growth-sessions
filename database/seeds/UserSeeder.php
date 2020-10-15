<?php

use App\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        if (empty(User::where('email', config('auth.slack_app_email'))->first())) {
            User::create([
                'name' => 'Slack App',
                'github_nickname' => 'slack',
                'email' => config('auth.slack_app_email'),
                'password' => \Illuminate\Support\Str::random(),
            ]);
        }
    }
}
