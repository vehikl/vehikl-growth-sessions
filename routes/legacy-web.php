<?php


use App\Http\Controllers\AnyDesksController;
use App\Http\Controllers\Api\DiscordChannelsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GrowthSessionController;
use App\Http\Controllers\ShowStatisticsController;
use App\Http\Controllers\TagsController;
use App\Http\Middleware\AuthenticateSlackApp;

Route::inertia('about', 'AboutPage')->name('about');
Route::get('login/{driver}', [LoginController::class, 'redirectToProvider'])->name('oauth.login.redirect');
Route::get('oauth/callback', [LoginController::class, 'handleProviderCallback']);
Route::get('oauth/{driver}/callback', [LoginController::class, 'handleProviderCallback']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::prefix('growth_sessions')->middleware(AuthenticateSlackApp::class)->name('growth_sessions.')->group(function() {
    Route::get('week', [GrowthSessionController::class, 'week'])->name('week');
    Route::get('day', [GrowthSessionController::class, 'day'])->name('day');
    Route::get('{growth_session}', [GrowthSessionController::class, 'show'])->name('show');
    Route::post('{growth_session}/join', [GrowthSessionController::class, 'join'])->middleware(['auth', 'can:join,growth_session'])->name('join');
    Route::post('{growth_session}/leave', [GrowthSessionController::class, 'leave'])->middleware(['auth', 'can:leave,growth_session'])->name('leave');

    Route::post('{growth_session}/watch', [GrowthSessionController::class, 'watch'])->middleware(['auth', 'can:watch,growth_session'])->name('watch');
});
Route::resource('growth_sessions', GrowthSessionController::class)->middleware('auth')->only(['store', 'update', 'destroy']);

Route::prefix('growth_sessions/{growth_session}/comments')->name('growth_sessions.comments.')->group(function() {
    Route::get('/', [CommentController::class, 'index'])->name('index');
});
Route::resource('growth_sessions.comments', CommentController::class)->middleware('auth')->only(['store','update','destroy']);

Route::prefix('api')->name('api.')->middleware('auth')->group(function () {
    Route::get('discord-channels',  [DiscordChannelsController::class, 'index']);
});

Route::get('/anydesks', [AnyDesksController::class, 'index'])->middleware('auth');
Route::get('/tags', [TagsController::class,'index'])->middleware('auth');

Route::get('/statistics', ShowStatisticsController::class)
    ->middleware(['auth', 'vehikl'])
    ->name('statistics.index');
