<?php

use App\Http\Middleware\AllowOnlyOwner;
use App\Http\Middleware\AuthenticateSlackApp;
use Illuminate\Support\Facades\Route;

Route::view('about','about')->name('about');
Route::get('login/{driver}', 'Auth\LoginController@redirectToProvider')->name('oauth.login.redirect');
Route::get('oauth/callback', 'Auth\LoginController@handleProviderCallback');
Route::get('oauth/{driver}/callback', 'Auth\LoginController@handleProviderCallback');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

Route::view('/', 'home')->name('home');

Route::prefix('social_mobs')->middleware(AuthenticateSlackApp::class)->name('growth_sessions.')->group(function() {
    Route::get('week', 'GrowthSessionController@week')->name('week');
    Route::get('day', 'GrowthSessionController@day')->name('day');
    Route::get('{social_mob}', 'GrowthSessionController@show')->name('show');
    Route::get('{social_mob}/edit', 'GrowthSessionController@edit')->middleware(['auth','can:update,social_mob'])->name('edit');
    Route::post('{social_mob}/join', 'GrowthSessionController@join')->middleware(['auth', 'can:join,social_mob'])->name('join');
    Route::post('{social_mob}/leave', 'GrowthSessionController@leave')->middleware(['auth', 'can:leave,social_mob'])->name('leave');
});
Route::resource('social_mobs', 'GrowthSessionController')->middleware('auth')->only(['store', 'update', 'destroy'])->names([
    'store' => 'growth_sessions.store',
    'update' => 'growth_sessions.update',
    'destroy' => 'growth_sessions.destroy',
]);

Route::prefix('social_mobs/{social_mob}/comments')->name('growth_sessions.comments.')->group(function() {
    Route::get('/', 'CommentController@index')->name('index');
});
Route::resource('social_mobs.comments', 'CommentController')->middleware('auth')->only(['store','update','destroy'])->names([
    'store' => 'growth_sessions.comments.store',
    'update' => 'growth_sessions.comments.update',
    'destroy' => 'growth_sessions.comments.destroy',
]);
