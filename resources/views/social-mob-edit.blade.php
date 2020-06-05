@extends('layouts.app')

@section('content')
    <mob-edit class="mx-auto my-10" :mob-json="{{json_encode($socialMob)}}" :user="{{json_encode(auth()->user())}}"></mob-edit>
@endsection
