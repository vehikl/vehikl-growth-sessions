<nav class="bg-blue-500 p-6">
    <div class="container flex items-center  mx-auto justify-between flex-wrap">
        <div class="flex items-center flex-shrink-0 mr-6 text-white hover:text-orange-200">
            <i class="fa fa-users text-2xl mr-2" aria-hidden="true"></i>
            <a href="{{ route('home') }}"
               class="font-semibold text-3xl">{{ config('app.name', 'Vehikl Growth Sessions') }}</a>
        </div>
        <div class="block lg:hidden">
            <button
                class="flex items-center px-3 py-2 border rounded text-white border-white hover:text-orange-200 hover:border-orange-200"
                onclick="document.getElementById('nav-links').classList.toggle('hidden')"
                @click="isExpanded = !isExpanded">
                <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <title>
                        Menu
                    </title>
                    <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/>
                </svg>
            </button>
        </div>
        <div
            class="w-full hidden text-xl uppercase lg:flex justify-end lg:items-center lg:w-auto text-center"
            id="nav-links">
            <div class="text-xl justify-center items-center flex flex-col lg:flex-row">
                @guest
                    <a href="{{route('oauth.login.redirect')}}"
                       class="flex items-center mt-4 lg:mt-0 mr-6 text-white hover:text-orange-200 ">
                        <i class="fa fa-github text-3xl mr-4" aria-hidden="true"></i> Login
                    </a>
                @endguest
                @auth
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="mt-4 flex items-center lg:mt-0 text-white hover:text-orange-200 ">
                        <v-avatar class="mr-4"
                                  src="{{ auth()->user()->avatar }}"
                                  alt="Your Avatar"
                                  size="12"></v-avatar>
                        Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endauth
                <a href="{{route('about')}}"
                   class="mt-4 lg:mt-0 ml-6 text-white hover:text-orange-200 ">About
                </a>
            </div>
        </div>
    </div>
</nav>
