<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { Nothingator } from '@/classes/Nothingator';
import { WeekGrowthSessions } from '@/classes/WeekGrowthSessions';
import DayView from '@/components/legacy/DayView.vue';
import GrowthSessionCard from '@/components/legacy/GrowthSessionCard.vue';
import GrowthSessionForm from '@/components/legacy/GrowthSessionForm.vue';
import GrowthSessionTags from '@/components/legacy/GrowthSessionTags.vue';
import SessionDetailDrawer from '@/components/legacy/SessionDetailDrawer.vue';
import VisibilityRadioFieldset from '@/components/legacy/VisibilityRadioFieldset.vue';
import VModal from '@/components/legacy/VModal.vue';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { IUser } from '@/types';
import { useEcho } from '@laravel/echo-vue';
import { watchDebounced } from '@vueuse/core';
import { computed, onBeforeMount, onBeforeUnmount, onMounted, ref } from 'vue';
import draggable from 'vuedraggable';

interface IGrowthSessionCardDragChange {
    added?: { element: GrowthSession; index: number };
    removed?: { element: GrowthSession; index: number };
}

defineProps<{ user?: IUser }>();
const referenceDate = ref(DateTime.today());
const growthSessions = ref<WeekGrowthSessions>(WeekGrowthSessions.empty());
const newGrowthSessionDate = ref('');
const growthSessionToUpdate = ref<GrowthSession | null>(null);
const draggedGrowthSession = ref<GrowthSession | null>(null);
const visibilityFilter = ref<'all' | 'public' | 'private'>('all');
const formModalState = ref<'open' | 'closed'>('closed');
const selectedTagIds = ref<number[]>([]);
const searchQuery = ref('');
const debouncedSearchQuery = ref('');

const view = ref<'week' | 'day'>('week');
const dayIndex = ref(0);
const filtersOpen = ref(false);
const selectedSession = ref<GrowthSession | null>(null);

const uniqueTags = computed(() => {
    const allTags = growthSessions.value.allGrowthSessions.flatMap((gs) => gs.tags);

    return allTags.filter((tag, index, allTags) => allTags.map((t) => t.id).indexOf(tag.id) == index);
});

const weekLabel = computed(() => {
    if (growthSessions.value.weekDates.length === 0) return '';
    return `${growthSessions.value.firstDay.format('MMM D')} – ${growthSessions.value.lastDay.format('MMM D')}`.toUpperCase();
});

const selectedDate = computed(() => growthSessions.value.weekDates[dayIndex.value] ?? growthSessions.value.weekDates[0] ?? DateTime.today());
const daySessions = computed(() => growthSessionsVisibleInDate(selectedDate.value));

watchDebounced(
    searchQuery,
    (newValue) => {
        debouncedSearchQuery.value = newValue;
    },
    { debounce: 300 },
);

onBeforeMount(async () => {
    await refreshGrowthSessionsOfTheWeek();
    const todayIdx = growthSessions.value.weekDates.findIndex((d) => d.isToday());
    dayIndex.value = todayIdx >= 0 ? todayIdx : 0;
    window.onpopstate = refreshGrowthSessionsOfTheWeek;
});

onMounted(() => {
    window.addEventListener('gs:create-session', handleHeaderCreate);
});

onBeforeUnmount(() => {
    window.onpopstate = null;
    window.removeEventListener('gs:create-session', handleHeaderCreate);
});

function handleHeaderCreate() {
    onCreateNewGrowthSessionClicked(selectedDate.value);
}

async function refreshGrowthSessionsOfTheWeek() {
    useDateFromUrlAsReference();
    await getAllGrowthSessionsOfTheWeek();
}

function growthSessionsVisibleInDate(date: DateTime) {
    const allGrowthSessionsOnDate = growthSessions.value.getSessionByDate(date);
    return allGrowthSessionsOnDate
        .filter((session) => {
            if (selectedTagIds.value.length == 0) return true;

            return session.tags.some((tag) => selectedTagIds.value.includes(tag.id));
        })
        .filter((session) => {
            if (visibilityFilter.value === 'private') {
                return !session.is_public;
            }

            if (visibilityFilter.value === 'public') {
                return session.is_public;
            }

            return true;
        })
        .filter((session) => {
            if (!debouncedSearchQuery.value) return true;

            const query = debouncedSearchQuery.value.toLowerCase();
            const title = session.title.toLowerCase();
            const topic = session.topic.toLowerCase();
            const ownerName = session.owner.name.toLowerCase();
            const attendeeMatch = session.attendees.some((attendee) => attendee.name.toLowerCase().includes(query));

            return title.includes(query) || topic.includes(query) || ownerName.includes(query) || attendeeMatch;
        });
}

function useDateFromUrlAsReference() {
    const urlSearchParams = new URLSearchParams(window.location.search);
    referenceDate.value = urlSearchParams.has('date') ? DateTime.parseByDate(urlSearchParams.get('date')!) : DateTime.today();
}

async function onDragEnd(location: any) {
    const targetDateString = location.to.dataset.date;
    if (!draggedGrowthSession.value || !targetDateString) {
        return;
    }
    try {
        await GrowthSessionApi.update(draggedGrowthSession.value, {
            date: targetDateString,
            attendee_limit: draggedGrowthSession.value.attendee_limit,
        });
    } catch {
        /* keep the board responsive even if the move failed to persist */
    }

    await getAllGrowthSessionsOfTheWeek();
}

function onChange(change: IGrowthSessionCardDragChange) {
    if (change.added) {
        return (draggedGrowthSession.value = change.added.element);
    }
}

async function getAllGrowthSessionsOfTheWeek() {
    growthSessions.value = await GrowthSessionApi.getAllGrowthSessionsOfTheWeek(referenceDate.value.toDateString());
}

async function onFormSubmitted() {
    await getAllGrowthSessionsOfTheWeek();
    formModalState.value = 'closed';
}

function onCreateNewGrowthSessionClicked(startDate: DateTime) {
    growthSessionToUpdate.value = null;
    newGrowthSessionDate.value = startDate.toDateString();
    formModalState.value = 'open';
}

function onGrowthSessionEditRequested(growthSession: GrowthSession) {
    selectedSession.value = null;
    growthSessionToUpdate.value = growthSession;
    newGrowthSessionDate.value = '';
    formModalState.value = 'open';
}

function onGrowthSessionCopyRequested(growthSession: GrowthSession) {
    selectedSession.value = null;
    growthSession.id = 0;
    growthSessionToUpdate.value = growthSession;
    newGrowthSessionDate.value = '';
    formModalState.value = 'open';
}

async function changeReferenceDate(deltaDays: number) {
    const next = DateTime.parseByDate(referenceDate.value.toDateString());
    next.addDays(deltaDays);
    referenceDate.value = next;
    window.history.pushState({}, document.title, `?date=${referenceDate.value.toDateString()}`);
    await getAllGrowthSessionsOfTheWeek();
}

function scrollToDate(id: string) {
    window.document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

function onTagClick(id: number) {
    if (selectedTagIds.value.includes(id)) {
        selectedTagIds.value = selectedTagIds.value.filter((tagId) => tagId != id);
    } else {
        selectedTagIds.value.push(id);
    }
}

function openDetail(growthSession: GrowthSession) {
    selectedSession.value = growthSession;
}

async function onDrawerRefresh() {
    const id = selectedSession.value?.id;
    await getAllGrowthSessionsOfTheWeek();
    if (id) {
        selectedSession.value = growthSessions.value.allGrowthSessions.find((s) => s.id === id) ?? null;
    }
}

async function refreshGrowthSessions(data: { type: string }) {
    const ignoredEvents = ['comment', 'watchers'];

    if (!ignoredEvents.includes(data.type)) {
        await getAllGrowthSessionsOfTheWeek();
    }
}

useEcho('gs-channel', '.session.modified', refreshGrowthSessions, [], 'public');
</script>

<template>
    <div v-if="growthSessions.isReady" class="gs-page flex min-h-[calc(100vh-56px)] flex-col">
        <!-- Control bar -->
        <div class="gs-bar gs-border relative flex flex-wrap items-center gap-3.5 border-b px-5 py-4 sm:px-7">
            <div class="flex flex-none items-center gap-2.5">
                <button
                    aria-label="Load previous week"
                    class="load-previous-week gs-seg gs-text-strong transition-smooth hover:text-gs-accent flex h-8 w-8 items-center justify-center rounded-md text-xl leading-none"
                    @click="changeReferenceDate(-7)"
                >
                    ‹
                </button>
                <span class="gs-text-strong text-sm font-semibold whitespace-nowrap">{{ weekLabel }}</span>
                <button
                    aria-label="Load next week"
                    class="load-next-week gs-seg gs-text-strong transition-smooth hover:text-gs-accent flex h-8 w-8 items-center justify-center rounded-md text-xl leading-none"
                    @click="changeReferenceDate(7)"
                >
                    ›
                </button>
            </div>

            <div class="gs-seg flex flex-none rounded-lg p-[3px]">
                <button
                    type="button"
                    class="transition-smooth rounded-md px-4 py-2 text-sm font-semibold whitespace-nowrap"
                    :class="view === 'week' ? 'gs-header-bg text-white' : 'gs-text-sub'"
                    @click="view = 'week'"
                >
                    Week
                </button>
                <button
                    type="button"
                    class="transition-smooth rounded-md px-4 py-2 text-sm font-semibold whitespace-nowrap"
                    :class="view === 'day' ? 'gs-header-bg text-white' : 'gs-text-sub'"
                    @click="view = 'day'"
                >
                    Day
                </button>
            </div>

            <div class="relative min-w-[180px] flex-1">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search sessions..."
                    class="search-input gs-input w-full rounded-lg px-4 py-2.5 text-sm"
                    aria-label="Search growth sessions by title, description, or host name"
                />
                <button
                    v-if="searchQuery"
                    class="gs-text-muted transition-smooth hover:text-gs-accent absolute top-1/2 right-2 -translate-y-1/2 rounded-md px-2 py-1"
                    aria-label="Clear search"
                    @click="searchQuery = ''"
                >
                    ✕
                </button>
            </div>

            <button
                v-if="uniqueTags.length"
                type="button"
                class="gs-seg gs-text-strong transition-smooth flex flex-none items-center gap-1.5 rounded-lg px-4 py-2.5 text-sm font-semibold whitespace-nowrap"
                @click="filtersOpen = !filtersOpen"
            >
                Filters
                <span
                    v-if="selectedTagIds.length"
                    class="gs-header-bg inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-xs font-bold text-white"
                    >{{ selectedTagIds.length }}</span
                >
            </button>

            <VisibilityRadioFieldset v-if="user && user.is_vehikl_member" id="visibility-filters" v-model="visibilityFilter" class="ml-auto" />
        </div>

        <!-- Filters panel -->
        <div v-show="filtersOpen" class="gs-col gs-border w-full border-b px-5 py-4 sm:px-7">
            <div class="mb-3 flex items-center justify-between">
                <span class="gs-text-sub text-xs font-bold tracking-[0.04em]">FILTER BY TAG</span>
                <button type="button" class="gs-accent-text text-sm font-semibold" @click="selectedTagIds = []">Clear</button>
            </div>
            <GrowthSessionTags
                ref="growthSessionTags"
                :tags="uniqueTags"
                :selected-tag-ids="selectedTagIds"
                class="flex-wrap"
                @tag-click="onTagClick"
            />
        </div>

        <!-- Week view -->
        <div v-show="view === 'week'" class="week-grid flex-1">
            <div
                v-for="date in growthSessions.weekDates"
                :key="date.toDateString()"
                :weekDay="date.weekDayString()"
                class="day gs-col gs-border flex flex-col border-r p-4"
            >
                <div class="mb-3.5 flex items-center justify-between">
                    <span :id="date.weekDayString()" class="gs-text-strong uppercase font-display text-base font-bold tracking-[0.04em]">{{
                        date.weekDayString()
                    }}</span>
                    <span class="gs-text-sub text-xs font-semibold tracking-[0.06em]">{{ date.format('MMM D').toUpperCase() }}</span>
                </div>

                <button
                    v-if="user && user.is_vehikl_member && !date.isInAPastDate()"
                    class="create-growth-session gs-btn-primary mx-2 mb-3 rounded-md px-4 py-2.5 text-sm font-semibold"
                    @click="onCreateNewGrowthSessionClicked(date)"
                >
                    + Add Session
                </button>

                <div v-show="growthSessionsVisibleInDate(date).length === 0" class="gs-text-muted px-2 py-6 text-center text-sm">
                    <p class="mb-1" v-text="`${Nothingator.random(date.toDateString())}...`" />
                    <p v-show="user && date.isToday()" class="gs-text-muted text-xs">Why don't you create the first one?</p>
                </div>

                <draggable
                    :model-value="growthSessionsVisibleInDate(date)"
                    :data-date="date.toDateString()"
                    item-key="id"
                    tag="ul"
                    class="h-full w-full"
                    group="growth-sessions"
                    handle=".handle"
                    @end="onDragEnd"
                    @change="onChange"
                >
                    <template #item="{ element: growthSession }">
                        <li :key="growthSession.id">
                            <growth-session-card
                                :growth-session="growthSession"
                                :user="user"
                                @growth-session-updated="getAllGrowthSessionsOfTheWeek"
                                @copy-requested="onGrowthSessionCopyRequested"
                                @edit-requested="onGrowthSessionEditRequested"
                                @delete-requested="getAllGrowthSessionsOfTheWeek"
                                @open-detail="openDetail"
                            />
                        </li>
                    </template>
                </draggable>
            </div>
        </div>

        <!-- Day view -->
        <DayView
            v-show="view === 'day'"
            :days="growthSessions.weekDates"
            :selected-index="dayIndex"
            :sessions="daySessions"
            :current-label="selectedDate.weekDayString()"
            :user="user"
            @select-day="dayIndex = $event"
            @open-detail="openDetail"
            @refresh="onDrawerRefresh"
            @edit-requested="onGrowthSessionEditRequested"
            @copy-requested="onGrowthSessionCopyRequested"
        />

        <!-- Scroll to today (mobile) -->
        <div v-if="growthSessions.hasCurrentDate" class="fixed inset-x-4 bottom-4 z-40 block md:hidden">
            <button
                aria-label="Scroll to today"
                class="gs-btn-primary flex w-full items-center justify-center rounded-md px-4 py-3 text-sm font-semibold shadow-lg"
                @click="scrollToDate(DateTime.today().weekDayString())"
            >
                Go to today
            </button>
        </div>

        <!-- Create / edit modal -->
        <v-modal :state="formModalState" @modal-closed="formModalState = 'closed'">
            <div class="gs-card relative rounded-2xl p-6">
                <button
                    class="gs-text-muted transition-smooth hover:text-gs-accent absolute top-5 right-5 text-xs font-semibold tracking-[0.04em]"
                    @click="formModalState = 'closed'"
                >
                    CLOSE ✕
                </button>
                <growth-session-form
                    v-if="formModalState === 'open'"
                    :growth-session="growthSessionToUpdate"
                    :owner="user"
                    :start-date="newGrowthSessionDate"
                    class="growth-session-form"
                    @submitted="onFormSubmitted"
                />
            </div>
        </v-modal>

        <!-- Detail drawer -->
        <SessionDetailDrawer
            v-if="selectedSession"
            :growth-session="selectedSession"
            :user="user"
            @close="selectedSession = null"
            @edit-requested="onGrowthSessionEditRequested"
            @refresh="onDrawerRefresh"
        />
    </div>
</template>

<style scoped>
.day {
    min-height: 14rem;
}

.week-grid {
    display: grid;
    grid-auto-flow: row;
    grid-template-columns: repeat(auto-fit, minmax(248px, 1fr));
    gap: 0;
    align-items: start;
}

@media (max-width: 767px) {
    .week-grid {
        grid-auto-rows: auto;
    }
}
</style>
