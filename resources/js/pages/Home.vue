<script setup lang="ts">
import Board from '@/pages/Board.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<{
    auth: {
        user: {
            id: number;
            name: string;
        };
    };
}>();

function getLoginUrl(driver = 'github'): string {
    return route('oauth.login.redirect', { driver });
}

const user = computed(() => page.props.auth.user ?? null);

const guestPerks = [
    { icon: 'fa-sign-in', label: 'Join sessions' },
    { icon: 'fa-plus', label: 'Host your own' },
    { icon: 'fa-compass', label: 'See where to go' },
];
</script>

<template>
    <Head title="Week" />

    <div class="flex min-h-[calc(100vh-128px)] flex-col">
        <section v-if="!user" class="gs-header-bg border-t border-white/10 text-white">
            <div class="flex flex-col gap-8 px-5 py-8 sm:px-7 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <p class="gs-accent-text text-xs font-semibold tracking-[0.18em] uppercase">You're browsing as a guest</p>
                    <h1 class="font-display mt-3 text-3xl font-semibold sm:text-4xl lg:text-5xl">Log in to join this week's sessions</h1>
                    <p class="mt-4 text-base text-white/70 sm:text-lg">
                        You can browse what's scheduled below. Log in to reserve a spot, host your own session, or see exactly where to find people.
                    </p>

                    <ul class="mt-7 flex flex-wrap gap-x-8 gap-y-4">
                        <li v-for="perk in guestPerks" :key="perk.label" class="flex items-center gap-3">
                            <span class="gs-accent-text flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-base">
                                <i :class="['fa', perk.icon]" aria-hidden="true"></i>
                            </span>
                            <span class="text-sm font-medium text-white/80">{{ perk.label }}</span>
                        </li>
                    </ul>
                </div>

                <div class="flex-none">
                    <a
                        :href="getLoginUrl('github')"
                        class="gs-btn-primary group shadow-vehikl-orange/30 inline-flex items-center justify-center gap-3 rounded-xl px-7 py-4 text-base font-semibold shadow-lg"
                    >
                        <i class="fa fa-github text-lg" aria-hidden="true"></i>
                        Continue with GitHub
                    </a>
                </div>
            </div>
        </section>

        <Board :user="$page.props.auth.user" />
    </div>

    <footer class="gs-bar gs-border border-t px-5 py-5 text-center">
        <p class="gs-text-muted text-sm">
            Have
            <a
                href="https://github.com/vehikl/vehikl-growth-sessions/issues"
                target="_blank"
                rel="noopener noreferrer"
                class="transition-smooth gs-accent-text underline decoration-transparent underline-offset-2 hover:decoration-current"
            >
                suggestions
            </a>
            or feedback?
            <a
                href="mailto:gsfeedback@vehikl.com"
                class="transition-smooth gs-accent-text underline decoration-transparent underline-offset-2 hover:decoration-current"
                >Let us know &rarr;</a
            >
        </p>
    </footer>
</template>
