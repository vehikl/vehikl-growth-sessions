<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo('/');

        $middleware->alias([
            'vehikl' => \App\Http\Middleware\VehiklMiddleware::class
        ]);
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('create:anydesk-reminder')
            ->twiceMonthly(1, 15);

        $schedule->command('statistics:recalculate')
            ->timezone('America/Toronto')
            ->at('01:00')
            ->daily();

        $schedule->command('statistics:recalculate')
            ->timezone('America/Toronto')
            ->at('12:30')
            ->daily();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
