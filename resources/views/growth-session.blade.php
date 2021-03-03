@extends('layouts.app')

@section('content')
    <growth-session-view class="mx-auto my-10"
                         :growth-session-json="{{json_encode($growthSession)}}"
                         :user-json="{{json_encode(auth()->user())}}"
                         :discord-guild-id="'{{config('services.discord.guild_id')}}'"></growth-session-view>
@endsection
