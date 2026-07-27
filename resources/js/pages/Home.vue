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
    services: {
        google_client_id?: string;
    };
}>();

function getLoginUrl(driver = 'github'): string {
    return route('oauth.login.redirect', { driver });
}

const user = computed(() => page.props.auth.user ?? null);
const shouldRenderGoogleLogin = computed(() => !!page.props.services.google_client_id);
</script>

<template>
    <Head title="Week" />

    <div
        v-if="!user"
        role="alert"
        class="border-vehikl-orange/20 bg-vehikl-orange fixed inset-x-4 bottom-4 z-30 block rounded-xl border p-5 text-center text-base text-white shadow-xl backdrop-blur-sm sm:bottom-4"
    >
        To join/create growth session or see their location, you must
        <strong>
            <a :href="getLoginUrl('github')" class="transition-smooth font-bold underline hover:text-white/80">log in with Github</a>
        </strong>
        <strong v-if="shouldRenderGoogleLogin">
            or <a :href="getLoginUrl('google')" class="transition-smooth font-bold underline hover:text-white/80">log in with Google</a>
        </strong>
    </div>

    <Board :user="$page.props.auth.user" />
</template>
