<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login/{driver}', [LoginController::class, 'redirectToProvider'])->name('oauth.login.redirect');
    Route::get('oauth/callback', [LoginController::class, 'handleProviderCallback']);
    Route::get('oauth/{driver}/callback', [LoginController::class, 'handleProviderCallback']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
