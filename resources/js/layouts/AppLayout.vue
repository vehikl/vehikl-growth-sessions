<script setup lang="ts">
import About from '@/components/About.vue';
import VAvatar from '@/components/legacy/VAvatar.vue';
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
    <nav class="border-b-2 border-gray-700 bg-gray-900 px-6 py-2">
        <div class="flex flex-wrap items-center justify-between">
            <div class="mr-6 flex flex-shrink-0 items-center text-white hover:text-orange-600 sm:text-lg">
                <i class="fa fa-users mr-4" aria-hidden="true"></i>
                <Link :href="route('home')" class="font-semibold">Vehikl Growth Sessions</Link>
            </div>
            <div class="block lg:hidden">
                <button
                    class="flex items-center rounded border border-white px-3 py-2 text-white hover:border-orange-200 hover:text-orange-600"
                    onclick="document.getElementById('nav-links').classList.toggle('hidden')"
                    @click="isExpanded = !isExpanded"
                >
                    <svg class="h-3 w-3 fill-current" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <title>Menu</title>
                        <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z" />
                    </svg>
                </button>
            </div>
            <div class="hidden w-full justify-end text-center text-xl uppercase lg:flex lg:w-auto lg:items-center" id="nav-links">
                <div class="flex flex-col items-center justify-center text-xl tracking-wider text-white lg:flex-row">
                    <template v-if="!$page.props.auth.user">
                        <Link
                            :href="route('oauth.login.redirect', { driver: 'github' })"
                            class="mt-4 mr-6 flex items-center hover:text-orange-600 lg:mt-0"
                        >
                            <i class="fa fa-github mr-4 text-3xl" aria-hidden="true"></i> Login with Github
                        </Link>

                        <Link
                            v-if="$page.props.services.google_client_id"
                            :href="route('oauth.login.redirect', { driver: 'google' })"
                            class="mt-4 mr-6 flex items-center hover:text-orange-600 lg:mt-0"
                        >
                            <i class="fa fa-google mr-4 text-3xl" aria-hidden="true"></i> Login with Google
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('logout')" class="mt-4 flex items-center tracking-wider hover:text-orange-600 lg:mt-0">
                            <v-avatar class="mr-4" :src="$page.props.auth.user.avatar" alt="Your Avatar" :size="6"></v-avatar>
                            Logout
                        </Link>
                        <Link v-if="route().current('statistics.index')" :href="route('home')" class="mt-4 hover:text-orange-600 sm:ml-6 lg:mt-0">
                            Board
                        </Link>
                        <Link v-else :href="route('statistics.index')" class="mt-4 hover:text-orange-600 sm:ml-6 lg:mt-0"> Statistics </Link>
                    </template>
                    <Link :href="route('about')" class="mt-4 hover:text-orange-600 sm:ml-6 lg:mt-0">About</Link>
                </div>
            </div>
        </div>
    </nav>

    <slot />
</template>
