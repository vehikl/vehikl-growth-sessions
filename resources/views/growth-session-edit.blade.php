@extends('layouts.app')

@section('content')
    <growth-session-edit class="mx-auto my-10" :growth-session-json="{{json_encode($growthSession)}}" :user="{{json_encode(auth()->user())}}"></growth-session-edit>
@endsection
