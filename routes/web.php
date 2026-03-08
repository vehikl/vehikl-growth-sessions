<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('guest')->group(function () {
   Route::get('/slack/interactivity', [\App\Http\Controllers\Slack\Integration::class, 'interactivity'])->name('slack.interactivity');
});

require __DIR__.'/legacy-web.php';
require __DIR__.'/auth.php';
