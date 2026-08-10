<script lang="ts" setup>
import MemberAvatar from '@/components/MemberAvatar.vue';
import MemberStatistics from '@/components/MemberStatistics.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import TagUsageBars from '@/components/TagUsageBars.vue';
import { DateRange, formatCount, formatGrowthTime } from '@/lib/statistics';
import { IStatisticsDashboard, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<IStatisticsDashboard>();

const page = usePage<SharedData>();
const currentUserId = computed(() => page.props?.auth?.user?.id ?? null);

const isReloadingMembers = ref(false);

let cancelPendingReload: (() => void) | null = null;
let latestReload = 0;

/**
 * Only the member table depends on the date range, so a range change is a partial reload of that prop — which also
 * preserves the table's own filter and sort state.
 * The range rides in the query string, which is what makes a shared link land the recipient on the same window.
 *
 * Inertia runs reloads concurrently, so editing the start date and then the end date puts
 * two in flight. The earlier one is canceled outright: without that, a slow first
 * response can land last and snap the table back to a range the member has left behind.
 */
function reloadMembers({ startDate, endDate }: DateRange): void {
    cancelPendingReload?.();

    const reloadId = ++latestReload;

    router.reload({
        only: ['members', 'start_date', 'end_date'],
        data: { start_date: startDate, end_date: endDate },
        onCancelToken: ({ cancel }) => (cancelPendingReload = cancel),
        onStart: () => (isReloadingMembers.value = true),
        onFinish: () => {
            if (reloadId === latestReload) {
                cancelPendingReload = null;
                isReloadingMembers.value = false;
            }
        },
    });
}
</script>

<template>
    <Head title="Statistics" />

    <PageContainer>
        <PageHeader eyebrow="Statistics" badge="The Receipts" title="Growth Sessions, by the numbers">
            <template #lead>Who's hosting, who's showing up, and who you still haven't mobbed with.</template>
        </PageHeader>

        <section class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Statistics summary">
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="gs-accent-text font-display block text-4xl font-bold">{{ formatCount(summary.lifetime_sessions_count) }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Lifetime sessions</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="gs-text-strong font-display block text-4xl font-bold">{{ formatGrowthTime(summary.lifetime_minutes_count) }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Time of growth</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="font-display block text-4xl font-bold text-green-600">{{ formatCount(summary.sessions_this_week_count) }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Sessions this week</span>
            </article>
            <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <strong class="gs-text-strong font-display block text-4xl font-bold">{{
                    formatCount(summary.weekly_unique_participants_count)
                }}</strong>
                <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Unique participants</span>
            </article>
        </section>

        <div class="grid gap-6">
            <section class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                <h2 class="gs-text-strong mb-5 text-sm font-bold tracking-[0.05em] uppercase">Top hosts this week</h2>
                <div class="space-y-4">
                    <div v-for="host in top_hosts" :key="host.id" class="flex items-center gap-3">
                        <MemberAvatar data-testid="top-host-avatar" size="md" :name="host.name" :avatar="host.avatar" />
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
                <TagUsageBars :tags="tags">
                    <template #empty>
                        <div
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
                    </template>
                </TagUsageBars>
            </section>

            <MemberStatistics
                :members="members"
                :current-user-id="currentUserId"
                :start-date="start_date"
                :end-date="end_date"
                :first-session-date="first_session_date"
                :is-loading="isReloadingMembers"
                @range-change="reloadMembers"
            />
        </div>
    </PageContainer>
</template>
