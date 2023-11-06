@extends('layouts.app')

@section('content')
    @guest
        <p role="alert" class="fixed z-30 text-white inset-x-4 rounded-xl bottom-12 sm:bottom-2 block text-center bg-orange-600 text-xl p-4">To join/create growth session or see
            their location, you must
            <strong><a href="{{route('oauth.login.redirect', ['driver' => 'github'])}}"
                       class="underline hover:text-orange-400">log in with Github</a></strong>
            @if(config('services.google.client_id'))
                or
                <strong><a href="{{route('oauth.login.redirect', ['driver' => 'google'])}}"
                           class="underline hover:text-orange-400">log in with Google</a></strong>
            @endif
            !
        </p>
    @endguest
    <week-view :user="{{ json_encode(auth()->user()) }}"></week-view>

    <div class="bg-blue-800">
        @include('partials.about')

        <section class="flex flex-col lg:flex-row px-6 sm:px-24 pt-8 pb-12 justify-between text-blue-100 bg-blue-600">
            <div class="text-center py-16 h-64">
                <p class="text-4xl tracking-wide mt-4 mb-4">Have <span class="italic font-semibold text-white">suggestions</span> for this app?</p>
                <a class="inline-block transform hover:-skew-y-2 hover:text-5xl bg-blue-600 border-4 border-white hover:bg-blue-700 focus:bg-blue-700 text-white text-2xl font-bold py-2 px-4 mt-2"
                target="_blank"
                href="https://github.com/vehikl/vehikl-growth-sessions/issues">
                    Share them!
                </a>
            </div>

            <div class="text-center py-16 h-64">
                <p class="text-4xl tracking-wide mt-4 mb-4">Have <span class="italic font-semibold text-white">feedback</span> on our <span class="italic font-semibold text-white">growth sessions</span>? </p>
                <p class="inline-block transform hover:-skew-y-2 hover:text-5xl bg-blue-600 border-4 border-white hover:bg-blue-700 focus:bg-blue-700 text-white text-2xl font-bold py-2 px-4 mt-2">
                    <a href="mailto:gsfeedback@vehikl.com">Send it to us!</a>
            </div>
        </section>

    </div>
@endsection
