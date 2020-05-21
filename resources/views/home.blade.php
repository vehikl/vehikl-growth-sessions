@extends('layouts.app')

@section('content')
    @include('partials.about')
    <week-view class="mt-6" :user="{{ json_encode(auth()->user()) }}"></week-view>
@endsection
