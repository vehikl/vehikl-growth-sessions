<script lang="ts" setup>
import type { IHostedSession, IPaginated } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{ sessions: IPaginated<IHostedSession> }>();

const hasRows = computed(() => props.sessions.data.length > 0);
const onFirstPage = computed(() => props.sessions.current_page <= 1);
const onLastPage = computed(() => props.sessions.current_page >= props.sessions.last_page);

const pageButtonClass =
    'gs-btn-secondary cursor-pointer rounded-lg px-3 py-1.5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-40';
// Paging is a partial reload of this prop alone, so the rest of the Dashboard stays put.
const pageVisitOptions = { only: ['hosted_sessions'], preserveScroll: true, preserveState: true };
</script>

<template>
    <div v-if="hasRows">
        <!-- A stacked list rather than a table: every field stays readable at any width without a
             horizontal scroller or a second set of markup for small screens. -->
        <ul>
            <li
                v-for="session in sessions.data"
                :key="session.id"
                class="gs-border flex items-start justify-between gap-4 border-b py-5 first:pt-0 last:border-b-0 last:pb-0"
            >
                <div class="min-w-0">
                    <Link
                        :href="route('growth_sessions.show', { growth_session: session.id })"
                        class="gs-text-strong break-words-fixed text-lg font-bold hover:underline"
                    >
                        {{ session.title }}
                    </Link>

                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                        <span class="gs-text-muted flex items-center gap-2 text-sm">
                            <span class="h-2 w-2 flex-none rounded-full" :class="session.is_upcoming ? 'bg-gs-upcoming' : 'bg-gs-finished'"></span>
                            <span class="sr-only">{{ session.is_upcoming ? 'Upcoming' : 'Finished' }}</span>
                            {{ session.date_label }}
                        </span>
                        <span class="gs-text-muted text-sm">{{ session.time_label }}</span>
                        <span
                            v-for="tag in session.tags"
                            :key="tag.id"
                            class="bg-vehikl-orange/10 gs-accent-text rounded-full px-2 py-0.5 text-[0.65rem] font-bold tracking-[0.08em] uppercase"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </div>

                <p class="flex-none text-right">
                    <strong class="gs-text-strong font-display block text-2xl font-bold tabular-nums">{{ session.attendee_count }}</strong>
                    <span class="gs-text-muted block text-[0.65rem] font-bold tracking-[0.06em] uppercase">Attendees</span>
                </p>
            </li>
        </ul>

        <div class="gs-border mt-5 flex flex-wrap items-center justify-between gap-4 border-t pt-4">
            <p class="gs-text-muted text-xs font-semibold tracking-[0.04em] uppercase">
                Showing {{ sessions.from }}–{{ sessions.to }} of {{ sessions.total }}
            </p>

            <div class="flex items-center gap-2">
                <Link
                    v-bind="pageVisitOptions"
                    as="button"
                    data-testid="previous-page"
                    :href="sessions.prev_page_url ?? ''"
                    :disabled="onFirstPage"
                    :class="pageButtonClass"
                >
                    Previous
                </Link>
                <Link
                    v-bind="pageVisitOptions"
                    as="button"
                    data-testid="next-page"
                    :href="sessions.next_page_url ?? ''"
                    :disabled="onLastPage"
                    :class="pageButtonClass"
                >
                    Next
                </Link>
            </div>
        </div>
    </div>

    <div
        v-else
        class="gs-secondary-bg gs-border flex w-full flex-col items-center justify-center rounded-xl border border-dashed px-6 py-10 text-center"
    >
        <span class="gs-card gs-border gs-accent-text mb-3 flex h-11 w-11 items-center justify-center rounded-full border text-lg shadow-sm">
            <i class="fa fa-calendar-o" aria-hidden="true"></i>
        </span>
        <strong class="gs-text-strong text-sm font-semibold">You haven't hosted any Growth Sessions yet.</strong>
        <p class="gs-text-body mt-1 max-w-md text-sm">
            Head to the
            <Link data-testid="empty-board-link" :href="route('home')" class="gs-accent-text font-semibold hover:underline">Board</Link>
            to schedule your first one.
        </p>
    </div>
</template>
