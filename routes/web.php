<?php

use App\Http\Controllers\ShowStatisticsController;
use App\Http\Middleware\AuthenticateSlackApp;
use Illuminate\Support\Facades\Route;

Route::view('about','about')->name('about');
Route::get('login/{driver}', 'Auth\LoginController@redirectToProvider')->name('oauth.login.redirect');
Route::get('oauth/callback', 'Auth\LoginController@handleProviderCallback');
Route::get('oauth/{driver}/callback', 'Auth\LoginController@handleProviderCallback');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

Route::view('/', 'home')->name('home');

Route::prefix('growth_sessions')->middleware(AuthenticateSlackApp::class)->name('growth_sessions.')->group(function() {
    Route::get('week', 'GrowthSessionController@week')->name('week');
    Route::get('day', 'GrowthSessionController@day')->name('day');
    Route::get('{growth_session}', 'GrowthSessionController@show')->name('show');
    Route::post('{growth_session}/join', 'GrowthSessionController@join')->middleware(['auth', 'can:join,growth_session'])->name('join');
    Route::post('{growth_session}/leave', 'GrowthSessionController@leave')->middleware(['auth', 'can:leave,growth_session'])->name('leave');

    Route::post('{growth_session}/watch', 'GrowthSessionController@watch')->middleware(['auth', 'can:watch,growth_session'])->name('watch');
});
Route::resource('growth_sessions', 'GrowthSessionController')->middleware('auth')->only(['store', 'update', 'destroy']);

Route::prefix('growth_sessions/{growth_session}/comments')->name('growth_sessions.comments.')->group(function() {
    Route::get('/', 'CommentController@index')->name('index');
});
Route::resource('growth_sessions.comments', 'CommentController')->middleware('auth')->only(['store','update','destroy']);

Route::prefix('api')->name('api.')->middleware('auth')->group(function () {
    Route::get('discord-channels', 'Api\\DiscordChannelsController@index');
});

Route::get('/anydesks', 'AnyDesksController@index')->middleware('auth');
Route::get('/tags', 'TagsController@index')->middleware('auth');

Route::get('/statistics', ShowStatisticsController::class)
    ->middleware(['auth', 'vehikl'])
    ->name('statistics.index');
