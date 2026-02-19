<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DevMode\Authentication;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login/{driver}', [LoginController::class, 'redirectToProvider'])->name('oauth.login.redirect');
    Route::get('oauth/callback', [LoginController::class, 'handleProviderCallback']);
    Route::get('oauth/{driver}/callback', [LoginController::class, 'handleProviderCallback']);

    if (config('app.env') !== 'production') {
        Route::get('dev-mode/authentication/{token}', Authentication::class)->name('dev-mode.authentication');
    }
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
