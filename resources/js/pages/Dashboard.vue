<script lang="ts" setup>
import HostedSessionsList from '@/components/HostedSessionsList.vue';
import HostedSessionsSort from '@/components/HostedSessionsSort.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import MemberAvatar from '@/components/MemberAvatar.vue';
import MobSquadLeaderboard from '@/components/MobSquadLeaderboard.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import TagUsageBars from '@/components/TagUsageBars.vue';
import { IDashboard } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<IDashboard>();
</script>

<template>
    <Head title="Dashboard" />

    <PageContainer full-width>
        <PageHeader eyebrow="Dashboard" badge="Your Turf" title="Your Growth Sessions️">
            <template #lead>Sessions you've run, sessions coming up, and the people you still owe a mob.</template>
        </PageHeader>

        <div data-testid="dashboard-columns" class="grid grid-cols-1 items-start gap-4 lg:grid-cols-3">
            <div data-testid="dashboard-main-column" class="flex flex-col gap-4 lg:col-span-2">
                <section class="grid auto-rows-min grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Session summary">
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="gs-accent-text font-display block text-4xl font-bold">{{ summary.sessions_hosted_count }}</strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Sessions hosted</span>
                    </article>
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="gs-text-strong font-display block text-4xl font-bold">{{ summary.sessions_attended_count }}</strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Sessions attended</span>
                    </article>
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="font-display block text-4xl font-bold text-green-600">{{ summary.upcoming_count }}</strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Upcoming</span>
                    </article>
                    <article class="gs-card gs-border rounded-xl border p-6 shadow-sm">
                        <strong class="font-display block text-4xl font-bold text-blue-600 dark:text-blue-400">
                            {{ summary.total_attendees_count }}
                        </strong>
                        <span class="gs-text-sub mt-2 block text-xs font-bold tracking-[0.06em] uppercase">Total attendees</span>
                    </article>
                </section>

                <section class="gs-card gs-border rounded-xl border p-4 shadow-sm sm:p-6" aria-labelledby="hosted-sessions-heading">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <h2 id="hosted-sessions-heading" class="gs-section-heading">Sessions you've hosted</h2>
                        <HostedSessionsSort v-if="hosted_sessions.total" :sort="sort" />
                    </div>
                    <HostedSessionsList :sessions="hosted_sessions" />
                </section>
            </div>

            <div data-testid="dashboard-sidebar" class="flex flex-col gap-4">
                <section v-if="mob_squad.length" class="gs-card gs-border rounded-xl border p-6 shadow-sm" aria-labelledby="mob-squad-heading">
                    <div class="mb-5 flex items-center gap-2">
                        <h2 id="mob-squad-heading" class="gs-section-heading">Mob Squad</h2>
                        <InfoPopover label="About Mob Squad">
                            The five people you've shared the most growth with. Bigger group sessions don't count &mdash; only the ones small enough
                            to really mob.
                        </InfoPopover>
                    </div>
                    <MobSquadLeaderboard :members="mob_squad" />
                </section>

                <!-- Like the Mob Squad above, an untagged user gets no card rather than a card
                     announcing its own emptiness. Any number from one up is still worth ranking. -->
                <section v-if="top_tags.length" class="gs-card gs-border rounded-xl border p-6 shadow-sm" aria-labelledby="top-tags-heading">
                    <h2 id="top-tags-heading" class="gs-section-heading mb-5">Top tags</h2>
                    <TagUsageBars :tags="top_tags" />
                </section>

                <section class="gs-card gs-border rounded-xl border p-6 shadow-sm" aria-labelledby="yet-to-mob-heading">
                    <h2 id="yet-to-mob-heading" class="gs-section-heading mb-5">Yet to mob with</h2>
                    <div v-if="yet_to_mob_with.length" class="flex flex-wrap gap-3">
                        <div
                            v-for="member in yet_to_mob_with"
                            :key="member.id"
                            class="gs-secondary-bg flex items-center gap-2.5 rounded-full py-1.5 pr-4 pl-1.5"
                        >
                            <MemberAvatar data-testid="yet-to-mob-avatar" :name="member.name" :avatar="member.avatar" />
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
            </div>
        </div>
    </PageContainer>
</template>
