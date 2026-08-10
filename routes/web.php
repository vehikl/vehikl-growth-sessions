<?php

use App\Http\Controllers\ShowDashboardController;
use App\Http\Controllers\Slack\Integration;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Home'))->name('home');

Route::get('dashboard', ShowDashboardController::class)->middleware('auth')->name('dashboard');

Route::middleware('guest')->group(function () {
   Route::get('/slack/interactivity', [Integration::class, 'interactivity'])->name('slack.interactivity');
});

require __DIR__.'/legacy-web.php';
require __DIR__.'/auth.php';
