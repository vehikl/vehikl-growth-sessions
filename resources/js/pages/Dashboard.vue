<script lang="ts" setup>
import HostedSessionsTable from '@/components/HostedSessionsTable.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { IDashboard } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<IDashboard>();
</script>

<template>
    <Head title="Dashboard" />

    <PageContainer>
        <PageHeader eyebrow="Dashboard" title="Your Growth Sessions">
            <template #lead>Everything you've hosted, newest first — including what you have coming up.</template>
        </PageHeader>

        <!-- The Dashboard is a container: sessions attended and watched become sibling sections here. -->
        <section class="gs-card gs-border rounded-xl border p-4 shadow-sm sm:p-6" aria-labelledby="hosted-sessions-heading">
            <h2 id="hosted-sessions-heading" class="gs-text-strong mb-5 text-sm font-bold tracking-[0.05em] uppercase">Sessions you've hosted</h2>
            <HostedSessionsTable :sessions="hosted_sessions" />
        </section>
    </PageContainer>
</template>
