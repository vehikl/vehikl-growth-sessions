@extends('layouts.app')

@section('content')
    @include('partials.about')
    <week-view class="mt-6" :user="{{ json_encode(auth()->user()) }}"></week-view>
    <a class="inline-block bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded mx-2 my-10"
       target="_blank"
       href="https://github.com/FRFlor/social-mob/issues">
        Have suggestions for this app? Please, share them here!
    </a>
@endsection
