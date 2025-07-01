<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { Nothingator } from '@/classes/Nothingator';
import { WeekGrowthSessions } from '@/classes/WeekGrowthSessions';
import GrowthSessionCard from '@/components/legacy/GrowthSessionCard.vue';
import GrowthSessionForm from '@/components/legacy/GrowthSessionForm.vue';
import GrowthSessionTags from '@/components/legacy/GrowthSessionTags.vue';
import VisibilityRadioFieldset from '@/components/legacy/VisibilityRadioFieldset.vue';
import VModal from '@/components/legacy/VModal.vue';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { IUser } from '@/types';
import { computed, onBeforeMount, onBeforeUnmount, ref } from 'vue';

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

const uniqueTags = computed(() => {
    const allTags = growthSessions.value.allGrowthSessions.flatMap((gs) => gs.tags);

    return allTags.filter((tag, index, allTags) => allTags.map((t) => t.id).indexOf(tag.id) == index);
});

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
        });
}

function useDateFromUrlAsReference() {
    const urlSearchParams = new URLSearchParams(window.location.search);
    referenceDate.value = urlSearchParams.has('date') ? DateTime.parseByDate(urlSearchParams.get('date')!) : DateTime.today();
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
</script>

<template>
    <div v-if="growthSessions.isReady">
        <div class="flex justify-between bg-gray-900 px-4 py-2 text-xl text-white sm:justify-center sm:text-3xl md:text-4xl">
            <button aria-label="Load previous week" class="load-previous-week pr-8 text-3xl sm:text-3xl" @click="changeReferenceDate(-7)">
                <i aria-hidden="true" class="fa fa-chevron-left"></i>
            </button>
            <h2
                v-if="growthSessions.weekDates.length > 0"
                class="mx-4 flex flex-col items-center justify-center text-center sm:flex-row md:w-full md:max-w-md"
            >
                {{ growthSessions.firstDay.format('MMMM DD') }} <span class="mx-4 text-slate-600">to</span>
                {{ growthSessions.lastDay.format('MMMM DD') }}
            </h2>
            <button aria-label="Load next week" class="load-next-week pl-8 text-3xl sm:text-3xl" @click="changeReferenceDate(+7)">
                <i aria-hidden="true" class="fa fa-chevron-right"></i>
            </button>
        </div>

        <div class="mx-4 flex flex-row justify-between gap-2 rounded-b-xl bg-slate-200 px-4 py-2 text-sm tracking-wide text-gray-800 sm:text-lg">
            <GrowthSessionTags :tags="uniqueTags" :selected-tag-ids="selectedTagIds" @tag-click="onTagClick" ref="growthSessionTags" />
            <VisibilityRadioFieldset v-if="user && user.is_vehikl_member" id="visibility-filters" v-model="visibilityFilter" />
        </div>

        <div class="week-grid gap-4 px-4 py-6">
            <v-modal :state="formModalState" @modal-closed="formModalState = 'closed'">
                <div class="relative flex flex-row-reverse flex-wrap overflow-visible">
                    <button
                        class="x absolute -top-2 right-2 rounded-full border-4 border-gray-900 bg-gray-900 px-4 py-1 text-white hover:border-gray-900 hover:bg-white hover:text-gray-900"
                        @click="formModalState = 'closed'"
                    >
                        <i aria-hidden="true" class="fa fa-times text-xl"></i> Close
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
                    'bg-slate-100': date.isEvenDate(),
                    'bg-slate-50': !date.isEvenDate(),
                }"
                class="day relative mb-2 flex flex-col items-center bg-slate-100"
            >
                <h2
                    class="sticky top-0 z-20 w-full rounded-t-xl bg-white p-2 pt-4 pb-1 text-center text-3xl font-bold tracking-wide text-slate-600 sm:relative"
                    v-text="date.weekDayString()"
                    :id="date.weekDayString()"
                ></h2>
                <p
                    v-if="date.isInAPastDate()"
                    class="w-full border-4 bg-slate-200 px-4 py-1 text-center text-lg font-semibold tracking-widest text-slate-600 uppercase"
                >
                    Finished
                </p>
                <button
                    v-if="user && user.is_vehikl_member && !date.isInAPastDate()"
                    class="create-growth-session mb-2 block w-full border-4 border-gray-600 bg-white px-4 py-1 text-lg font-semibold tracking-widest text-gray-600 uppercase hover:border-gray-600 hover:bg-gray-600 hover:text-white"
                    @click="onCreateNewGrowthSessionClicked(date)"
                >
                    <i aria-hidden="true" class="fa fa-plus-circle mr-4"></i><span class="text">Add Session</span>
                </button>
                <div v-show="growthSessionsVisibleInDate(date).length === 0" class="px-4 py-8 text-center text-xl text-gray-600">
                    <p v-text="`${Nothingator.random()}...`" />
                    <p v-show="user && date.isToday()">Why don't you create the first one?</p>
                </div>
                <ul :date="date" item-key="id" class="h-full w-full py-2" group="growth-sessions" handle=".handle">
                    <li v-for="growthSession in growthSessionsVisibleInDate(date)" :key="growthSession.id">
                        <growth-session-card
                            :growth-session="growthSession"
                            :user="user"
                            @growth-session-updated="getAllGrowthSessionsOfTheWeek"
                            @copy-requested="onGrowthSessionCopyRequested"
                            @edit-requested="onGrowthSessionEditRequested"
                            @delete-requested="getAllGrowthSessionsOfTheWeek"
                        />
                    </li>
                </ul>
            </div>
            <div class="fixed inset-x-4 bottom-4 z-50 block md:hidden" v-if="growthSessions.hasCurrentDate">
                <button
                    aria-label="Scroll to today"
                    class="flex w-full items-center justify-center rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
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
    min-height: 12rem;
    @media (min-width: 768px) {
        min-height: 40rem;
    }
    background-color: #f9fafb;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.week-grid {
    display: grid;
    grid-auto-flow: row;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    @media (max-width: 767px) {
        grid-auto-rows: auto;
    }
}

button {
    transition:
        background-color 0.3s ease,
        color 0.3s ease;
    &:hover {
        background-color: #4a5568;
        color: #ffffff;
    }
}

h2 {
    font-family: 'Roboto', sans-serif;
    color: #2d3748;
}
</style>
