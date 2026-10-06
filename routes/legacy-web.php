<?php


use App\Http\Controllers\AnyDesksController;
use App\Http\Controllers\Api\DiscordChannelsController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GrowthSessionController;
use App\Http\Controllers\GrowthSessionInvitationController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\ShowStatisticsController;
use App\Http\Controllers\TagsController;

Route::inertia('about', 'AboutPage')->name('about');

Route::get('invitations/{token}', GrowthSessionInvitationController::class)->name('growth_sessions.invitation');

Route::prefix('growth_sessions')->name('growth_sessions.')->group(function() {
    Route::get('week', [GrowthSessionController::class, 'week'])->name('week');
    Route::get('day', [GrowthSessionController::class, 'day'])->name('day');
    Route::get('{growth_session}', [GrowthSessionController::class, 'show'])->name('show');
    Route::post('{growth_session}/join', [GrowthSessionController::class, 'join'])->middleware(['auth', 'can:join,growth_session'])->name('join');
    Route::post('{growth_session}/leave', [GrowthSessionController::class, 'leave'])->middleware(['auth', 'can:leave,growth_session'])->name('leave');

    Route::post('{growth_session}/watch', [GrowthSessionController::class, 'watch'])->middleware(['auth', 'can:watch,growth_session'])->name('watch');

    // Filing under a series is not editing, so it does not ride on `update` and its future-only rule.
    Route::put('{growth_session}/series', [SeriesController::class, 'file'])->middleware(['auth', 'can:fileInSeries,growth_session'])->name('series.file');
});
Route::resource('growth_sessions', GrowthSessionController::class)->middleware('auth')->only(['store', 'update', 'destroy']);

Route::prefix('growth_sessions/{growth_session}/comments')->name('growth_sessions.comments.')->group(function() {
    Route::get('/', [CommentController::class, 'index'])->name('index');
});
Route::resource('growth_sessions.comments', CommentController::class)->middleware('auth')->only(['store','update','destroy']);

Route::prefix('api')->name('api.')->middleware('auth')->group(function () {
    Route::get('discord-channels',  [DiscordChannelsController::class, 'index']);
    Route::get('discord-channels/{date}/occupied', [DiscordChannelsController::class, 'occupied']);
});

Route::get('/anydesks', [AnyDesksController::class, 'index'])->middleware('auth');
Route::get('/tags', [TagsController::class,'index'])->middleware('auth');
Route::get('/series', [SeriesController::class, 'index'])->middleware('auth')->name('series.index');

Route::get('/statistics', ShowStatisticsController::class)
    ->middleware(['auth', 'vehikl'])
    ->name('statistics.index');

Route::prefix('proposals')->middleware('auth')->name('proposals.')->group(function () {
    Route::get('/', [\App\Http\Controllers\GrowthSessionProposalController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\GrowthSessionProposalController::class, 'store'])->name('store');
    Route::get('{proposal}', [\App\Http\Controllers\GrowthSessionProposalController::class, 'show'])->name('show');
    Route::put('{proposal}', [\App\Http\Controllers\GrowthSessionProposalController::class, 'update'])->name('update');
    Route::delete('{proposal}', [\App\Http\Controllers\GrowthSessionProposalController::class, 'destroy'])->name('destroy');
    Route::post('{proposal}/approve', [\App\Http\Controllers\GrowthSessionProposalController::class, 'approve'])->name('approve');
});
