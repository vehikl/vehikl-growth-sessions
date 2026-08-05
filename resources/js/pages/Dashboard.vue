<script lang="ts" setup>
import HostedSessionsList from '@/components/HostedSessionsList.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { IDashboard } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<IDashboard>();
</script>

<template>
    <Head title="Dashboard" />

    <PageContainer>
        <PageHeader eyebrow="Dashboard" badge="Your Turf" title="Your Growth Sessions️">
            <template #lead>Everything you've hosted, newest first — including what you have coming up.</template>
        </PageHeader>

        <section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Hosting summary">
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="gs-accent-text font-display block text-4xl font-bold">{{ summary.sessions_hosted_count }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Sessions hosted</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="font-display block text-4xl font-bold text-green-600">{{ summary.upcoming_count }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Upcoming</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="font-display block text-4xl font-bold text-blue-600 dark:text-blue-400">{{ summary.total_attendees_count }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Total attendees</span>
            </article>
        </section>

        <!-- The Dashboard is a container: sessions attended and watched become sibling sections here. -->
        <section class="gs-card gs-border rounded-xl border p-4 shadow-sm sm:p-6" aria-labelledby="hosted-sessions-heading">
            <h2 id="hosted-sessions-heading" class="sr-only">Sessions you've hosted</h2>
            <HostedSessionsList :sessions="hosted_sessions" />
        </section>
    </PageContainer>
</template>
