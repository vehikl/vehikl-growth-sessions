<script setup lang="ts">
import VAvatar from '@/components/legacy/VAvatar.vue';
import VehiklLogo from '@/svgs/VehiklLogo.vue';
import type { BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const isExpanded = ref(false);
</script>

<template>
    <nav class="border-b-2 border-vehikl-dark bg-vehikl-dark px-6 py-3">
        <div class="flex flex-wrap items-center justify-between">
            <div class="mr-6 flex flex-shrink-0 items-center">
                <Link :href="route('home')" class="transition-smooth hover:opacity-80 flex gap-4 items-center">
                    <VehiklLogo />
                    <span class="hidden lg:block text-2xl font-bold text-white tracking-wider leading-none">GROWTH SESSIONS</span>
                </Link>
            </div>
            <div class="block lg:hidden">
                <button
                    class="flex items-center rounded border border-white px-3 py-2 text-white hover:border-vehikl-orange hover:text-vehikl-orange transition-smooth"
                    onclick="document.getElementById('nav-links').classList.toggle('hidden')"
                    @click="isExpanded = !isExpanded"
                >
                    <svg class="h-3 w-3 fill-current" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <title>Menu</title>
                        <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z" />
                    </svg>
                </button>
            </div>
            <div class="hidden w-full justify-end text-center text-base uppercase lg:flex lg:w-auto lg:items-center" id="nav-links">
                <div class="flex flex-col items-center justify-center text-base tracking-wider text-white lg:flex-row">
                    <template v-if="!$page.props.auth.user">
                        <a
                            :href="route('oauth.login.redirect', { driver: 'github' })"
                            class="mt-4 mr-6 flex items-center hover:text-vehikl-orange transition-smooth lg:mt-0"
                        >
                            <i class="fa fa-github mr-3 text-2xl" aria-hidden="true"></i> Login with Github
                        </a>

                        <a
                            v-if="$page.props.services.google_client_id"
                            :href="route('oauth.login.redirect', { driver: 'google' })"
                            class="mt-4 mr-6 flex items-center hover:text-vehikl-orange transition-smooth lg:mt-0"
                        >
                            <i class="fa fa-google mr-3 text-2xl" aria-hidden="true"></i> Login with Google
                        </a>
                    </template>
                    <template v-else>
                        <Link :href="route('logout')" method="post" as="button" class="mt-4 flex items-center tracking-wider hover:text-vehikl-orange transition-smooth lg:mt-0">
                            <v-avatar class="mr-3" :src="$page.props.auth.user.avatar" alt="Your Avatar" :size="6"></v-avatar>
                            Logout
                        </Link>
                        <template v-if="$page.props.auth.user.is_vehikl_member">
                            <Link v-if="route().current('statistics.index')" :href="route('home')" class="mt-4 hover:text-vehikl-orange transition-smooth sm:ml-6 lg:mt-0">
                                Board
                            </Link>
                            <Link v-else :href="route('statistics.index')" class="mt-4 hover:text-vehikl-orange transition-smooth sm:ml-6 lg:mt-0"> Statistics </Link>
                        </template>
                        <Link v-if="route().current('proposals.index')" :href="route('home')" class="mt-4 hover:text-vehikl-orange transition-smooth sm:ml-6 lg:mt-0">
                            Board
                        </Link>
                        <Link v-else :href="route('proposals.index')" class="mt-4 hover:text-vehikl-orange transition-smooth sm:ml-6 lg:mt-0">Proposals</Link>
                    </template>
                    <Link v-if="route().current('about')" :href="route('home')" class="mt-4 hover:text-vehikl-orange transition-smooth sm:ml-6 lg:mt-0">
                        Board
                    </Link>
                    <Link v-else :href="route('about')" class="mt-4 hover:text-vehikl-orange transition-smooth sm:ml-6 lg:mt-0">About</Link>
                </div>
            </div>
        </div>
    </nav>
    <slot />
</template>
