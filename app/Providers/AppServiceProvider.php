<?php

namespace App\Providers;

use App\Http\Resources\GrowthSession as GrowthSessionResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any   services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Bootstrap any ap lication services.
     *
     * @return void
     */
    public function boot()
    {
        GrowthSessionResource::withoutWrapping();
        Model::shouldBeStrict(!$this->app->environment('production'));
    }
}
