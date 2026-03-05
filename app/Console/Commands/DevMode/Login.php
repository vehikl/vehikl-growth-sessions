<?php

namespace App\Console\Commands\DevMode;

use App\Models\User;
use Illuminate\Console\Command;
use function Laravel\Prompts\select;

class Login extends Command
{
    protected $signature = 'dev-mode:login-as {github_nickname?}';

    protected $description = 'Make a login link for a given user';

    public function handle()
    {
        $user = $this->getUser();

        $link = route('oauth.login.redirect', ['driver' => 'github', 'github_user' => $user->github_nickname], true);
        $this->info("Here's your login link: " . $link);
    }

    protected function getUser(): User
    {
        if ($this->argument('github_nickname')) {
            return User::query()
                ->where('github_nickname', $this->argument('github_nickname'))
                ->firstOrFail();
        }

        $githubUser = select(
            'Choose a user',
            User::all()->reduce(function (array $options, User $user) {
                $options[$user->github_nickname] = $user->github_nickname;
                return $options;
            }, [])
        );

        return User::query()
            ->where('github_nickname', $githubUser)
            ->firstOrFail();
    }
}
