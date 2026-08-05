<script lang="ts" setup>
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useInitials } from '@/composables/useInitials';
import { avatarColor } from '@/lib/sessionDisplay';
import { IStatisticsDashboard } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<IStatisticsDashboard>();

const { getInitials } = useInitials();

const maxTagCount = computed(() => Math.max(1, ...props.tags.map((tag) => tag.sessions_count)));
</script>

<template>
    <Head title="Statistics" />

    <PageContainer>
        <PageHeader eyebrow="Statistics" badge="The Receipts" title="Growth Sessions, by the numbers">
            <template #lead>Who's hosting, who's showing up, and who you still haven't mobbed with.</template>
        </PageHeader>

        <section class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Statistics summary">
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="gs-accent-text font-display block text-4xl font-bold">{{ summary.lifetime_sessions_count }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Lifetime sessions</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="font-display block text-4xl font-bold text-green-600">{{ summary.sessions_this_week_count }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Sessions this week</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="gs-text-strong font-display block text-4xl font-bold">{{ summary.weekly_unique_participants_count }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Unique participants</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="gs-text-strong font-display block text-4xl font-bold">{{ summary.average_attendance_count }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Average attendance</span>
            </article>
        </section>

        <div class="grid gap-6">
            <section class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <h2 class="gs-text-strong mb-5 text-sm font-bold tracking-[0.05em] uppercase">Top hosts this week</h2>
                <div class="space-y-4">
                    <div v-for="host in top_hosts" :key="host.id" class="flex items-center gap-3">
                        <span
                            :style="{ backgroundColor: avatarColor(host.name) }"
                            class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-xs font-bold text-white"
                            >{{ getInitials(host.name) }}</span
                        >
                        <span class="gs-text-strong min-w-0 flex-1 truncate text-sm font-semibold">{{ host.name }}</span>
                        <span class="gs-text-sub text-sm font-semibold"
                            >{{ host.sessions_hosted_count }} {{ host.sessions_hosted_count === 1 ? 'session' : 'sessions' }}</span
                        >
                    </div>
                    <p v-if="top_hosts.length === 0" class="gs-text-muted py-6 text-center text-sm">No hosted sessions this week.</p>
                </div>
            </section>

            <section class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <h2 class="gs-text-strong mb-5 text-sm font-bold tracking-[0.05em] uppercase">Sessions by tag</h2>
                <div v-if="tags.length" class="flex flex-col gap-4">
                    <div v-for="tag in tags" :key="tag.id" class="flex items-center gap-4">
                        <span class="gs-text-muted w-32 flex-none truncate text-xs font-semibold tracking-[0.06em] uppercase">
                            {{ tag.name }}
                        </span>
                        <div class="gs-secondary-bg relative h-2.5 flex-1 overflow-hidden rounded-full">
                            <div
                                class="gs-accent-bg absolute inset-y-0 left-0 rounded-full"
                                :style="{ width: `${(tag.sessions_count / maxTagCount) * 100}%` }"
                            ></div>
                        </div>
                        <span class="gs-text-strong w-6 flex-none text-right text-sm font-bold tabular-nums">{{ tag.sessions_count }}</span>
                    </div>
                </div>
                <div
                    v-else
                    class="gs-secondary-bg gs-border flex w-full flex-col items-center justify-center rounded-xl border border-dashed px-6 py-10 text-center"
                >
                    <span
                        class="gs-card gs-border gs-accent-text mb-3 flex h-11 w-11 items-center justify-center rounded-full border text-lg shadow-sm"
                    >
                        <i class="fa fa-tags" aria-hidden="true"></i>
                    </span>
                    <strong class="gs-text-strong text-sm font-semibold">No tagged sessions yet</strong>
                    <p class="gs-text-body mt-1 max-w-md text-sm">Tags will appear here when this week's sessions are categorized.</p>
                </div>
            </section>
        </div>
    </PageContainer>
</template>
