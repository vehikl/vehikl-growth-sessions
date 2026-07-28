<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { IStatistics, IUserStatistics } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeMount, ref } from 'vue';

const users = ref<IUserStatistics[]>([]);
const summary = ref<IStatistics['summary']>();
const tags = ref<NonNullable<IStatistics['tags']>>([]);
const currentUser = ref<IUserStatistics | null>(null);
const isLoading = ref(true);

const today = new DateTime();
const weekday = today.weekDayNumber();
const daysSinceMonday = weekday === 0 ? 6 : weekday - 1;
const startDate = new DateTime(today.toDateString()).addDays(-daysSinceMonday).toDateString();
const endDate = today.toDateString();

const dashboardSummary = computed(() => ({
    lifetime_sessions_count: summary.value?.lifetime_sessions_count ?? 0,
    sessions_this_week_count: summary.value?.sessions_this_week_count ?? users.value.reduce((total, user) => total + user.sessions_hosted_count, 0),
    weekly_unique_participants_count: summary.value?.weekly_unique_participants_count ?? 0,
    average_attendance_count: summary.value?.average_attendance_count ?? 0,
}));

const topHosts = computed(() =>
    [...users.value]
        .filter((user) => user.sessions_hosted_count > 0)
        .sort((a, b) => b.sessions_hosted_count - a.sessions_hosted_count || a.name.localeCompare(b.name))
        .slice(0, 5),
);

const yetToMobWith = computed(() => currentUser.value?.has_not_mobbed_with ?? []);

const sortedTags = computed(() => [...tags.value].sort((a, b) => b.sessions_count - a.sessions_count || a.name.localeCompare(b.name)));

const maxTagCount = computed(() => Math.max(1, ...tags.value.map((tag) => tag.sessions_count)));

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

const avatarColors = [
    'bg-blue-600',
    'bg-fuchsia-600',
    'bg-orange-500',
    'bg-yellow-600',
    'bg-green-600',
    'bg-teal-500',
    'bg-purple-600',
    'bg-rose-600',
    'bg-cyan-600',
    'bg-indigo-600',
    'bg-emerald-600',
    'bg-pink-600',
    'bg-amber-600',
    'bg-sky-600',
    'bg-violet-600',
];

function avatarColor(name: string): string {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash << 5) - hash + name.charCodeAt(i);
        hash |= 0;
    }
    return avatarColors[Math.abs(hash) % avatarColors.length];
}

async function fetchStatistics() {
    isLoading.value = true;

    try {
        const query = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
        });
        const response = await axios.get<IStatistics>(`/statistics?${query}`);

        users.value = response.data.users;
        summary.value = response.data.summary;
        tags.value = response.data.tags ?? [];
        currentUser.value = response.data.current_user ?? null;
    } finally {
        isLoading.value = false;
    }
}

onBeforeMount(fetchStatistics);
</script>

<template>
    <Head title="Statistics" />

    <main class="gs-page min-h-[calc(100vh-64px)] px-5 py-10 sm:px-8">
        <div class="mx-auto max-w-6xl">
            <header class="mb-8">
                <h1 class="gs-text-strong font-display text-4xl font-bold">Statistics</h1>
                <p class="gs-text-body mt-2 text-lg">A snapshot of Growth Sessions activity and participation.</p>
            </header>

            <div v-if="isLoading" class="gs-text-muted py-24 text-center text-sm">Loading statistics…</div>

            <template v-else>
                <section class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Statistics summary">
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="gs-accent-text font-display block text-4xl font-bold">{{ dashboardSummary.lifetime_sessions_count }}</strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Lifetime sessions</span>
                    </article>
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="font-display block text-4xl font-bold text-green-600">{{ dashboardSummary.sessions_this_week_count }}</strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Sessions this week</span>
                    </article>
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="gs-text-strong font-display block text-4xl font-bold">{{
                            dashboardSummary.weekly_unique_participants_count
                        }}</strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Unique participants</span>
                    </article>
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="gs-text-strong font-display block text-4xl font-bold">{{ dashboardSummary.average_attendance_count }}</strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Average attendance</span>
                    </article>
                </section>

                <div class="mb-8 grid gap-6">
                    <section class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <h2 class="gs-text-strong mb-5 text-sm font-bold tracking-[0.05em] uppercase">Top hosts this week</h2>
                        <div class="space-y-4">
                            <div v-for="host in topHosts" :key="host.user_id" class="flex items-center gap-3">
                                <span
                                    :class="avatarColor(host.name)"
                                    class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-xs font-bold text-white"
                                    >{{ initials(host.name) }}</span
                                >
                                <span class="gs-text-strong min-w-0 flex-1 truncate text-sm font-semibold">{{ host.name }}</span>
                                <span class="gs-text-sub text-sm font-semibold"
                                    >{{ host.sessions_hosted_count }} {{ host.sessions_hosted_count === 1 ? 'session' : 'sessions' }}</span
                                >
                            </div>
                            <p v-if="topHosts.length === 0" class="gs-text-muted py-6 text-center text-sm">No hosted sessions this week.</p>
                        </div>
                    </section>

                    <section class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <h2 class="gs-text-strong mb-5 text-sm font-bold tracking-[0.05em] uppercase">Sessions by tag</h2>
                        <div v-if="tags.length" class="flex flex-col gap-4">
                            <div v-for="tag in sortedTags" :key="tag.id" class="flex items-center gap-4">
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

                <section class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                    <h2 class="gs-text-strong mb-5 text-sm font-bold tracking-[0.05em] uppercase">Yet to mob with</h2>
                    <div v-if="yetToMobWith.length" class="flex flex-wrap gap-3">
                        <div v-for="member in yetToMobWith" :key="member.id" class="gs-secondary-bg flex items-center gap-2.5 rounded-full py-1.5 pr-4 pl-1.5">
                            <span
                                :class="avatarColor(member.name)"
                                class="flex h-8 w-8 flex-none items-center justify-center rounded-full text-[11px] font-bold text-white"
                                >
                                {{ initials(member.name) }}
                            </span>
                            <span class="gs-text-strong text-xs font-semibold whitespace-nowrap">{{ member.name }}</span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="gs-secondary-bg gs-border flex flex-col items-center justify-center rounded-xl border border-dashed px-6 py-10 text-center"
                    >
                        <span
                            class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-green-100 text-lg text-green-700 dark:bg-green-900/30 dark:text-green-400"
                        >
                            <i class="fa fa-check" aria-hidden="true"></i>
                        </span>
                        <strong class="gs-text-strong text-sm font-semibold">You're all caught up!</strong>
                        <p class="gs-text-body mt-1 max-w-md text-sm">
                            You've mobbed with everyone on your current list. Nice work building connections.
                        </p>
                    </div>
                </section>
            </template>
        </div>
    </main>
</template>
