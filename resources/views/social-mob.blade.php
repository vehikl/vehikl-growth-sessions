@extends('layouts.app')

@section('content')
    <mob-view class="mx-auto my-10" :mob="{{json_encode($socialMob)}}" :user="{{json_encode(auth()->user())}}"></mob-view>
@endsection
