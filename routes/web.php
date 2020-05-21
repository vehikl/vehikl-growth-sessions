<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
Route::view('about','about')->name('about');
Route::get('login/github', 'Auth\LoginController@redirectToProvider')->name('oauth.login.redirect');
Route::get('oauth/callback', 'Auth\LoginController@handleProviderCallback')->name('oauth.login.callback');

Route::get('/', 'HomeController@index')->name('home');
Route::get('social_mob/week', 'SocialMobController@week')->name('social_mob.week');
Route::post('social_mob/{social_mob}/join', 'SocialMobController@join')->name('social_mob.join');
Route::post('social_mob/{social_mob}/leave', 'SocialMobController@leave')->name('social_mob.leave');
Route::resource('social_mob', 'SocialMobController');
