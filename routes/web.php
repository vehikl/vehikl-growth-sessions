<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();
Route::view('about','about')->name('about');
Route::get('login/github', 'Auth\LoginController@redirectToProvider')->name('oauth.login.redirect');
Route::get('oauth/callback', 'Auth\LoginController@handleProviderCallback')->name('oauth.login.callback');

Route::view('/', 'home')->name('home');

Route::prefix('social_mob')->name('social_mob.')->group(function() {
    Route::get('week', 'SocialMobController@week')->name('week');
    Route::get('day', 'SocialMobController@day')->name('day');
    Route::post('{social_mob}/join', 'SocialMobController@join')->name('join');
    Route::post('{social_mob}/leave', 'SocialMobController@leave')->name('leave');
});
Route::resource('social_mob', 'SocialMobController')->except(['create']);

Route::prefix('social_mob.comment')->get('/', 'CommentController@index');
Route::resource('social_mob.comment', 'CommentController')->middleware('auth')->only(['store','update','destroy']);
