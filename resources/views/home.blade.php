@extends('layouts.app')

@section('content')
    @guest
        <week-view></week-view>
    @endguest

    @auth
        <week-view :user="{{ json_encode(auth()->user()) }}"></week-view>
    @endauth
@endsection
