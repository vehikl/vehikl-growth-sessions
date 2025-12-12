<script lang="ts" setup>
import {IGrowthSession, IUser} from '@/types';
import GrowthSessionCard from '@/components/legacy/GrowthSessionCard.vue';
import GrowthSessionForm from '@/components/legacy/GrowthSessionForm.vue';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { WeekGrowthSessions } from '@/classes/WeekGrowthSessions';
import { Nothingator } from '@/classes/Nothingator';
import VisibilityRadioFieldset from '@/components/legacy/VisibilityRadioFieldset.vue';
import VModal from '@/components/legacy/VModal.vue';
import { computed, onBeforeMount, onBeforeUnmount, ref } from 'vue';
import { watchDebounced } from '@vueuse/core';
import GrowthSessionTags from '@/components/legacy/GrowthSessionTags.vue';
import draggable from 'vuedraggable';
import {useEcho} from "@laravel/echo-vue";

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

const uniqueTags = computed(() => {
    const allTags = growthSessions.value.allGrowthSessions.flatMap((gs) => gs.tags);

    return allTags.filter((tag, index, allTags) => allTags.map((t) => t.id).indexOf(tag.id) == index);
});

watchDebounced(searchQuery, (newValue) => {
    debouncedSearchQuery.value = newValue;
}, { debounce: 300 });

onBeforeMount(async () => {
    await refreshGrowthSessionsOfTheWeek();
    window.onpopstate = refreshGrowthSessionsOfTheWeek;
});

onBeforeUnmount(() => {
    window.onpopstate = null;
});

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

            return title.includes(query) || topic.includes(query) || ownerName.includes(query);
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
    } catch (e) {}

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
    growthSessionToUpdate.value = growthSession;
    newGrowthSessionDate.value = '';
    formModalState.value = 'open';
}

function onGrowthSessionCopyRequested(growthSession: GrowthSession) {
    growthSession.id = 0;
    growthSessionToUpdate.value = growthSession;
    newGrowthSessionDate.value = '';
    formModalState.value = 'open';
}

async function changeReferenceDate(deltaDays: number) {
    referenceDate.value.addDays(deltaDays);
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

useEcho('gs-channel', '.session.modified', getAllGrowthSessionsOfTheWeek, [], "public");

</script>

<template>
    <div v-if="growthSessions.isReady">
        <div class="flex justify-between bg-vehikl-dark px-4 py-4 text-xl text-white sm:justify-center sm:text-2xl shadow-md">
            <button aria-label="Load previous week" class="load-previous-week pr-8 text-2xl sm:text-2xl hover:text-vehikl-orange transition-smooth rounded-lg px-3 py-2 hover:bg-white/10" @click="changeReferenceDate(-7)">
                <i aria-hidden="true" class="fa fa-chevron-left"></i>
            </button>
            <h2
                v-if="growthSessions.weekDates.length > 0"
                class="mx-4 flex flex-col items-center justify-center text-center sm:flex-row md:w-full md:max-w-md font-semibold"
            >
                {{ growthSessions.firstDay.format('MMMM DD') }} <span class="mx-4 text-neutral-400">to</span>
                {{ growthSessions.lastDay.format('MMMM DD') }}
            </h2>
            <button aria-label="Load next week" class="load-next-week pl-8 text-2xl sm:text-2xl hover:text-vehikl-orange transition-smooth rounded-lg px-3 py-2 hover:bg-white/10" @click="changeReferenceDate(+7)">
                <i aria-hidden="true" class="fa fa-chevron-right"></i>
            </button>
        </div>

        <div class="mx-4 flex flex-col gap-3 rounded-b-xl bg-neutral-100 px-4 py-3 text-sm tracking-wide text-neutral-700 shadow-sm sm:text-base border border-t-0 border-neutral-200 md:flex-row md:items-center">
            <div class="relative flex-shrink-0 md:w-64">
                <i aria-hidden="true" class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search sessions..."
                    class="search-input w-full rounded-lg border border-neutral-300 bg-white py-2 pl-10 pr-10 text-sm text-neutral-700 placeholder-neutral-400 transition-smooth focus:border-vehikl-orange focus:outline-none focus:ring-2 focus:ring-vehikl-orange/20"
                    aria-label="Search growth sessions by title, description, or host name"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-neutral-400 hover:text-neutral-600 transition-smooth"
                    aria-label="Clear search"
                >
                    <i aria-hidden="true" class="fa fa-times"></i>
                </button>
            </div>
            <GrowthSessionTags :tags="uniqueTags" :selected-tag-ids="selectedTagIds" @tag-click="onTagClick" ref="growthSessionTags" class="flex-shrink justify-center mx-auto flex-wrap" />
            <VisibilityRadioFieldset v-if="user && user.is_vehikl_member" id="visibility-filters" v-model="visibilityFilter" class="flex-shrink-0" />
        </div>

        <div class="week-grid gap-4 px-4 py-6">
            <v-modal :state="formModalState" @modal-closed="formModalState = 'closed'">
                <div class="relative flex flex-row-reverse flex-wrap overflow-visible p-6 bg-white rounded-2xl">
                    <button
                        class="x absolute top-4 right-4 rounded-lg bg-neutral-100 hover:bg-neutral-200 px-4 py-2 text-neutral-700 hover:text-vehikl-dark transition-smooth font-semibold"
                        @click="formModalState = 'closed'"
                    >
                        <i aria-hidden="true" class="fa fa-times mr-2"></i> Close
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
            <div
                v-for="date in growthSessions.weekDates"
                :key="date.toDateString()"
                :weekDay="date.weekDayString()"
                :class="{
                    'bg-neutral-50': date.isEvenDate(),
                    'bg-white': !date.isEvenDate(),
                }"
                class="day relative mb-2 flex flex-col items-center rounded-lg overflow-hidden border border-neutral-200 shadow-sm"
            >
                <h2
                    class="sticky top-0 z-20 w-full bg-gradient-to-b from-white to-neutral-50 p-3 text-center text-2xl font-bold tracking-wide text-vehikl-dark border-b border-neutral-200 sm:relative"
                    v-text="date.weekDayString()"
                    :id="date.weekDayString()"
                ></h2>
                <p
                    v-if="date.isInAPastDate()"
                    class="w-full bg-neutral-100 px-4 py-2 text-center text-sm font-semibold tracking-wider text-neutral-500 uppercase border-b border-neutral-200"
                >
                    Finished
                </p>
                <button
                    v-if="user && user.is_vehikl_member && !date.isInAPastDate()"
                    class="create-growth-session mb-2 mx-2 mt-3 block w-[calc(100%-1rem)] rounded-lg bg-vehikl-orange hover:bg-vehikl-orange/90 px-4 py-2.5 text-base font-semibold tracking-wide text-white transition-smooth shadow-sm hover:shadow-md"
                    @click="onCreateNewGrowthSessionClicked(date)"
                >
                    <i aria-hidden="true" class="fa fa-plus-circle mr-2"></i><span class="text">Add Session</span>
                </button>
                <div v-show="growthSessionsVisibleInDate(date).length === 0" class="px-4 py-8 text-center text-lg text-neutral-500">
                    <p class="mb-2" v-text="`${Nothingator.random(date.toDateString())}...`" />
                    <p v-show="user && date.isToday()" class="text-sm text-neutral-400">Why don't you create the first one?</p>
                </div>
                <draggable
                    :model-value="growthSessionsVisibleInDate(date)"
                    :data-date="date.toDateString()"
                    item-key="id"
                    tag="ul"
                    class="h-full w-full py-2"
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
                            />
                        </li>
                    </template>
                </draggable>
            </div>
            <div class="fixed inset-x-4 bottom-4 z-50 block md:hidden" v-if="growthSessions.hasCurrentDate">
                <button
                    aria-label="Scroll to today"
                    class="flex w-full items-center justify-center rounded-lg bg-vehikl-orange hover:bg-vehikl-orange/90 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:shadow-xl transition-smooth focus:ring-2 focus:ring-vehikl-orange/50 focus:ring-offset-2 focus:outline-none"
                    @click="scrollToDate(DateTime.today().weekDayString())"
                >
                    Go to today <i aria-hidden="true" class="fa fa-calendar ml-2"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.day {
    min-height: 10rem;
    @media (min-width: 768px) {
        min-height: 35rem;
    }
}

.week-grid {
    display: grid;
    grid-auto-flow: row;
    grid-template-columns: repeat(auto-fit, minmax(228px, 1fr));
    @media (max-width: 767px) {
        grid-auto-rows: auto;
    }
}
</style>
