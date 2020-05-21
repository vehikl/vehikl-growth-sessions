<nav class="bg-blue-500 p-6">
    <div class="container flex items-center  mx-auto justify-between flex-wrap">
        <div class="flex items-center flex-shrink-0 mr-6 text-white hover:text-orange-200">
            <i class="fa fa-users text-2xl mr-2" aria-hidden="true"></i>
            <a href="{{ route('home') }}"
               class="font-semibold text-3xl">{{ config('app.name', 'Social Mob') }}</a>
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
        <div class="w-full hidden lg:flex justify-end lg:items-center lg:w-auto text-center" id="nav-links">
            <div class="text-xl">
                @guest
                    <a href="{{route('oauth.login.redirect')}}"
                       class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-orange-200 mr-6">
                        <i class="fa fa-github text-xl" aria-hidden="true"></i> Login
                    </a>
                    <a href="{{ route('login') }}"
                       class="sr-only block mt-4 lg:inline-block lg:mt-0 text-white hover:text-orange-200 mr-6">
                        Login with SocialMob
                    </a>
                    <a href="{{ route('register') }}"
                       class="sr-only block mt-4 lg:inline-block lg:mt-0 text-white hover:text-orange-200">
                        Register
                    </a>
                @endguest
                @auth
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="mt-4 flex items-center lg:mt-0 text-white hover:text-orange-200">
                        <div class="w-12 h-12 mr-4 relative">
                            <div class="group w-full h-full rounded-full overflow-hidden shadow-inner">
                                <img src="{{ auth()->user()->avatar }}" alt="Your Avatar"
                                     class="object-cover object-center w-full h-full visible group-hover:hidden"/>
                            </div>
                        </div>
                        Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endguest
            </div>
        </div>
    </div>
</nav>
