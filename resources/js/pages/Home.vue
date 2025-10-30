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
    <p v-if="!user" role="alert" class="fixed inset-x-4 bottom-12 z-30 block rounded-xl bg-orange-600 p-4 text-center text-xl text-white sm:bottom-2">
            To join/create growth session or see their location, you must
            <strong>
                <a :href="getLoginUrl('github')" class="underline hover:text-orange-400">log in with Github</a>
            </strong>
            <strong v-if="shouldRenderGoogleLogin">
                <a :href="getLoginUrl('google')" class="underline hover:text-orange-400">log in with Google</a>
            </strong>
        </p>

    <week-view :user="$page.props.auth.user"/>

    <div class="bg-gray-800">
        <About />

        <section class="flex flex-col justify-between bg-gray-600 px-6 pt-8 pb-12 text-gray-100 sm:px-24 lg:flex-row">
            <div class="h-64 py-16 text-center">
                <p class="mt-4 mb-4 text-4xl tracking-wide">Have <span class="font-semibold text-white italic">suggestions</span> for this app?</p>
                <a
                    class="mt-2 inline-block transform border-4 border-white bg-gray-600 px-4 py-2 text-2xl font-bold text-white hover:-skew-y-2 hover:bg-gray-700 hover:text-5xl focus:bg-gray-700"
                    target="_blank"
                    href="https://github.com/vehikl/vehikl-growth-sessions/issues"
                >
                    Share them!
                </a>
            </div>

            <div class="h-64 py-16 text-center">
                <p class="mt-4 mb-4 text-4xl tracking-wide">
                    Have <span class="font-semibold text-white italic">feedback</span> on our
                    <span class="font-semibold text-white italic">growth sessions</span>?
                </p>
                <p
                    class="mt-2 inline-block transform border-4 border-white bg-gray-600 px-4 py-2 text-2xl font-bold text-white hover:-skew-y-2 hover:bg-gray-700 hover:text-5xl focus:bg-gray-700"
                >
                    <a href="mailto:gsfeedback@vehikl.com">Send it to us!</a>
                </p>
            </div>
        </section>
    </div>
</template>
