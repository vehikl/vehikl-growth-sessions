<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import WeekView from '@/components/legacy/WeekView.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import About from '@/components/About.vue';

const page = usePage<{
    auth: {
        user: {
            id: number;
            name: string;
        };
    },
    services: {
        google_client_id?: string;
    };
}>();

function getLoginUrl(driver = 'github'): string {
    return route('oauth.login.redirect', { driver });
}

const user = computed(() => page.props.auth.user ?? null);
const shouldRenderGoogleLogin = computed(() => !! page.props.services.google_client_id);
</script>

<template>
    <div v-if="!user" role="alert" class="fixed inset-x-4 bottom-4 z-30 block rounded-xl bg-vehikl-orange shadow-xl border border-vehikl-orange/20 p-5 text-center text-base text-white sm:bottom-4 backdrop-blur-sm">
            To join/create growth session or see their location, you must
            <strong>
                <a :href="getLoginUrl('github')" class="underline hover:text-white/80 transition-smooth font-bold">log in with Github</a>
            </strong>
            <strong v-if="shouldRenderGoogleLogin">
                or <a :href="getLoginUrl('google')" class="underline hover:text-white/80 transition-smooth font-bold">log in with Google</a>
            </strong>
        </div>

    <week-view :user="$page.props.auth.user"/>

    <div class="bg-vehikl-dark">
        <About />

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 px-6 py-12 sm:px-12 lg:px-24 bg-gradient-to-b from-vehikl-dark to-black">
            <div class="text-center lg:text-left p-8 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:border-vehikl-orange/50 transition-smooth">
                <p class="mb-6 text-3xl font-semibold text-white leading-tight">Have <span class="text-vehikl-orange italic">suggestions</span> for this app?</p>
                <a
                    class="inline-block rounded-lg bg-vehikl-orange hover:bg-vehikl-orange/90 px-6 py-3 text-lg font-semibold text-white transition-smooth shadow-lg hover:shadow-xl hover-lift"
                    target="_blank"
                    href="https://github.com/vehikl/vehikl-growth-sessions/issues"
                >
                    Share them!
                </a>
            </div>

            <div class="text-center lg:text-left p-8 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:border-vehikl-orange/50 transition-smooth">
                <p class="mb-6 text-3xl font-semibold text-white leading-tight">
                    Have <span class="text-vehikl-orange italic">feedback</span> on our <span class="text-vehikl-orange italic">growth sessions</span>?
                </p>
                <a
                    href="mailto:gsfeedback@vehikl.com"
                    class="inline-block rounded-lg bg-white hover:bg-neutral-100 px-6 py-3 text-lg font-semibold text-vehikl-dark transition-smooth shadow-lg hover:shadow-xl hover-lift"
                >
                    Send it to us!
                </a>
            </div>
        </section>
    </div>
</template>
