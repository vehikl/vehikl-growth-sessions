<?php

use App\User;
use Illuminate\Database\Migrations\Migration;

class AddSlackUser extends Migration
{
    public function up()
    {
        if (empty(User::where('email', config('auth.slack_app_email'))->first())) {
            User::create([
                'name' => 'Slack App',
                'avatar' => 'https://avatars.githubusercontent.com/u/6425636?s=200&v=4',
                'github_nickname' => 'slack',
                'email' => config('auth.slack_app_email'),
                'password' => \Illuminate\Support\Str::random(),
            ]);
        }
    }

    public function down()
    {
        User::where('email', config('auth.slack_app_email'))->first()->forceDelete();
    }
}
