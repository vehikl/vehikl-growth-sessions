@extends('layouts.app')

@section('content')
    @include('partials.about')
    @guest
        <p role="alert" class="block text-center text-orange-600 text-2xl">To join existing mobs or create your own, you must
            <strong><a href="{{route('oauth.login.redirect')}}" class="underline hover:text-orange-400">log in</a></strong>!
        </p>
    @endguest
    <week-view class="mt-6" :user="{{ json_encode(auth()->user()) }}"></week-view>

    <p class="text-lg mx-2 text-blue-700 font-bold mt-4">Have suggestions for this app? </p>
    <a class="inline-block bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded mx-2 mb-10 mt-2"
       target="_blank"
       href="https://github.com/FRFlor/social-mob/issues">
        Please, share them here!
    </a>
@endsection
