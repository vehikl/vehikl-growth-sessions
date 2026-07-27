<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';
import VehiklLogo from '@/svgs/VehiklLogo.vue';
import type { BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { theme, toggleTheme } = useTheme();
const isDark = computed(() => theme.value === 'dark');

const navLinkClass = 'text-sm font-medium uppercase tracking-[0.06em] text-white/70 transition-smooth hover:text-white cursor-pointer';

</script>

<template>
    <header class="gs-header-bg flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 sm:px-7">
        <div class="flex items-center gap-4">
            <Link :href="route('home')" class="transition-smooth flex items-center hover:opacity-80">
                <VehiklLogo />
            </Link>
            <span class="hidden h-6 w-px bg-white/15 sm:block"></span>
            <span class="hidden text-xs leading-none font-medium tracking-[0.28em] text-white/50 uppercase sm:block"> Growth Sessions </span>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-x-5 gap-y-2 sm:gap-x-6">
            <template v-if="$page.props.auth.user">
                <template v-if="$page.props.auth.user.is_vehikl_member">
                    <Link v-if="route().current('statistics.index')" :href="route('home')" :class="navLinkClass">Board</Link>
                    <Link v-else :href="route('statistics.index')" :class="navLinkClass">Statistics</Link>
                </template>

                <Link v-if="route().current('about')" :href="route('home')" :class="navLinkClass">Board</Link>
                <Link v-else :href="route('about')" :class="navLinkClass">About</Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    :class="[navLinkClass, 'flex items-center gap-2']"
                >
                    <i class="fa fa-sign-out text-base" aria-hidden="true"></i> Logout
                </Link>
            </template>

            <template v-else>
                <a
                    :href="route('oauth.login.redirect', { driver: 'github' })"
                    class="transition-smooth flex items-center gap-2 text-sm font-medium tracking-[0.06em] text-white/70 uppercase hover:text-white cursor-pointer"
                >
                    <i class="fa fa-github text-base" aria-hidden="true"></i> Login with GitHub
                </a>
                <a
                    v-if="$page.props.services?.google_client_id"
                    :href="route('oauth.login.redirect', { driver: 'google' })"
                    class="transition-smooth flex items-center gap-2 text-sm font-medium tracking-[0.06em] text-white/70 uppercase hover:text-white cursor-pointer"
                >
                    <i class="fa fa-google text-base" aria-hidden="true"></i> Login with Google
                </a>
                <Link v-if="route().current('about')" :href="route('home')" :class="navLinkClass">Board</Link>
                <Link v-else :href="route('about')" :class="navLinkClass">About</Link>
            </template>

            <button
                type="button"
                aria-label="Toggle light and dark theme"
                :title="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
                class="transition-smooth flex h-9 w-9 items-center justify-center rounded-md text-base text-white/70 hover:text-white cursor-pointer"
                @click="toggleTheme"
            >
                <span aria-hidden="true">{{ isDark ? '☀' : '☾' }}</span>
            </button>
        </div>
    </header>

    <slot />
</template>
