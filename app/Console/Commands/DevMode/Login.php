<?php

namespace App\Console\Commands\DevMode;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Login extends Command
{
    protected $signature = 'dev-mode:login-as {github_nickname}';

    protected $description = 'Make a login link for a given user';

    public function handle()
    {
        $user = User::query()
            ->where('github_nickname', $this->argument('github_nickname'))
            ->firstOrFail();

        $uuid = Str::uuid()->toString();
        Cache::put("dev-mode:auth-token:{$uuid}", $user->id, now()->addMinutes(10));

        $this->info("Here's your login link: " . route('dev-mode.authentication', ['token' => $uuid], true));
    }
}
