@extends('layouts.app')

@section('content')
    <growth-session-view class="mx-auto my-10" :growth-session-json="{{json_encode($growthSession)}}" :user-json="{{json_encode(auth()->user())}}"></growth-session-view>
@endsection
