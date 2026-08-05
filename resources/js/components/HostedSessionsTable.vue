<script lang="ts" setup>
import type { IHostedSession, IPaginated } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{ sessions: IPaginated<IHostedSession> }>();

const hasRows = computed(() => props.sessions.data.length > 0);
const onFirstPage = computed(() => props.sessions.current_page <= 1);
const onLastPage = computed(() => props.sessions.current_page >= props.sessions.last_page);

const headerClass = 'gs-text-muted px-2 py-2 text-left text-xs font-bold tracking-[0.06em] uppercase sm:px-3';
const cellClass = 'gs-text-body px-2 py-3 align-top text-sm sm:px-3';
const pageButtonClass =
    'gs-btn-secondary cursor-pointer rounded-lg px-3 py-1.5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-40';
// Paging is a partial reload of this prop alone, so the rest of the Dashboard stays put.
const pageVisitOptions = { only: ['hosted_sessions'], preserveScroll: true, preserveState: true };
</script>

<template>
    <div v-if="hasRows">
        <!-- Auto table layout with wrapping cells: the narrow columns shrink to their content and the
             session title absorbs the slack, so the table fits a phone without scrolling sideways. -->
        <table class="w-full border-collapse">
            <thead>
                <tr class="gs-border border-b">
                    <th scope="col" :class="headerClass">Date</th>
                    <th scope="col" :class="[headerClass, 'hidden sm:table-cell']">Time</th>
                    <th scope="col" :class="[headerClass, 'w-full']">Session</th>
                    <th scope="col" :class="[headerClass, 'text-right']">Attendees</th>
                    <th scope="col" :class="[headerClass, 'hidden md:table-cell']">Tags</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="session in sessions.data" :key="session.id" class="gs-border border-b last:border-b-0">
                    <td :class="cellClass">{{ session.date_label }}</td>
                    <td :class="[cellClass, 'hidden whitespace-nowrap sm:table-cell']">{{ session.time_label }}</td>
                    <td :class="cellClass">
                        <Link
                            :href="route('growth_sessions.show', { growth_session: session.id })"
                            class="gs-text-strong break-words-fixed font-semibold hover:underline"
                        >
                            {{ session.title }}
                        </Link>
                    </td>
                    <td :class="[cellClass, 'text-right tabular-nums']">{{ session.attendee_count }}</td>
                    <td :class="[cellClass, 'hidden md:table-cell']">
                        <span v-if="!session.tags.length" class="gs-text-muted">—</span>
                        <span
                            v-for="tag in session.tags"
                            :key="tag.id"
                            class="gs-secondary-bg gs-text-sub mr-1.5 mb-1.5 inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        >
                            {{ tag.name }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>

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
