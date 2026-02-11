<?php

namespace App\Providers;

use App\Services\Discord\DiscordService;
use App\Services\Discord\DiscordServiceFake;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        app()->bind(DiscordService::class, function () {
            return config('app.env') != 'production' && config('services.discord.use_fake')
                ? new DiscordServiceFake()
                : new DiscordService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
