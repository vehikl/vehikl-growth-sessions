<script lang="ts" setup>
import {IUser} from "../types"
import GrowthSessionCard from "./GrowthSessionCard.vue"
import GrowthSessionForm from "./GrowthSessionForm.vue"
import {GrowthSessionApi} from "../services/GrowthSessionApi"
import {DateTime} from "../classes/DateTime"
import Draggable from "vuedraggable"
import {GrowthSession} from "../classes/GrowthSession"
import {WeekGrowthSessions} from "../classes/WeekGrowthSessions"
import {Nothingator} from "../classes/Nothingator"
import VisibilityRadioFieldset from "./VisibilityRadioFieldset.vue"
import VModal from "./VModal.vue"
import {onBeforeMount, onBeforeUnmount, ref} from "vue"

interface IGrowthSessionCardDragChange {
    added?: { element: GrowthSession, index: number }
    removed?: { element: GrowthSession, index: number }
}

const props = defineProps<{ user?: IUser }>()
const referenceDate = ref(DateTime.today())
const growthSessions = ref<WeekGrowthSessions>(WeekGrowthSessions.empty())
const newGrowthSessionDate = ref("")
const growthSessionToUpdate = ref<GrowthSession | null>(null)
const draggedGrowthSession = ref<GrowthSession | null>(null)
const visibilityFilter = ref<"all" | "public" | "private">("all")
const formModalState = ref<"open" | "closed">("closed")

onBeforeMount(async () => {
    await refreshGrowthSessionsOfTheWeek()
    window.onpopstate = refreshGrowthSessionsOfTheWeek
})

onBeforeUnmount(() => {
    window.onpopstate = null
})

async function refreshGrowthSessionsOfTheWeek() {
    useDateFromUrlAsReference()
    await getAllGrowthSessionsOfTheWeek()
}

function growthSessionsVisibleInDate(date: DateTime) {
    let allGrowthSessionsOnDate = growthSessions.value.getSessionByDate(date)
    return allGrowthSessionsOnDate.filter((session) => {
        if (visibilityFilter.value === "private") {
            return !session.is_public
        }

        if (visibilityFilter.value === "public") {
            return session.is_public
        }

        return true
    })
}

function useDateFromUrlAsReference() {
    const urlSearchParams = new URLSearchParams(window.location.search)
    referenceDate.value = urlSearchParams.has("date")
        ? DateTime.parseByDate(urlSearchParams.get("date")!)
        : DateTime.today()
}

async function onDragEnd(location: any) {
    let targetDate = location.to.__vueParentComponent.attrs.date
    if (!draggedGrowthSession.value) {
        return
    }
    try {
        await GrowthSessionApi.update(draggedGrowthSession.value, {
            date: targetDate.toDateString(),
            attendee_limit: draggedGrowthSession.value.attendee_limit
        })
    } catch (e) {
    }

    await getAllGrowthSessionsOfTheWeek()
}

function onChange(change: IGrowthSessionCardDragChange) {
    if (change.added) {
        return draggedGrowthSession.value = change.added.element
    }
}


async function getAllGrowthSessionsOfTheWeek() {
    growthSessions.value = await GrowthSessionApi.getAllGrowthSessionsOfTheWeek(referenceDate.value.toDateString())
}

async function onFormSubmitted() {
    await getAllGrowthSessionsOfTheWeek()
    formModalState.value = "closed"
}

function onCreateNewGrowthSessionClicked(startDate: DateTime) {
    growthSessionToUpdate.value = null
    newGrowthSessionDate.value = startDate.toDateString()
    formModalState.value = "open"
}

function onGrowthSessionEditRequested(growthSession: GrowthSession) {
    growthSessionToUpdate.value = growthSession
    newGrowthSessionDate.value = ""
    formModalState.value = "open"
}

function onGrowthSessionCopyRequested(growthSession: GrowthSession) {
    growthSession.id = 0
    growthSessionToUpdate.value = growthSession
    newGrowthSessionDate.value = ""
    formModalState.value = "open"
}

async function changeReferenceDate(deltaDays: number) {
    referenceDate.value.addDays(deltaDays)
    window.history.pushState({}, document.title, `?date=${referenceDate.value.toDateString()}`)
    await getAllGrowthSessionsOfTheWeek()
}

function scrollToDate(id: string) {
    window.document.getElementById(id)?.scrollIntoView({behavior: "smooth"})
}
</script>

<template>
    <div v-if="growthSessions.isReady">
        <div class="flex justify-between sm:justify-center text-xl sm:text-3xl md:text-4xl bg-gray-900 text-white px-4 py-2">
            <button aria-label="Load previous week"
                    class="load-previous-week text-3xl sm:text-3xl pr-8"
                    @click="changeReferenceDate(-7)">
                <i aria-hidden="true" class="fa fa-chevron-left"></i>
            </button>
            <h2 class="flex flex-col sm:flex-row items-center mx-4 text-center" v-if="growthSessions.weekDates.length > 0">
                {{ growthSessions.firstDay.format("MMMM DD") }} <span class="text-slate-400 mx-4">to</span>
                {{ growthSessions.lastDay.format("MMMM DD") }}
            </h2>
            <button aria-label="Load next week"
                    class="load-next-week text-3xl sm:text-3xl pl-8"
                    @click="changeReferenceDate(+7)">
                <i aria-hidden="true" class="fa fa-chevron-right"></i>
            </button>
        </div>

        <VisibilityRadioFieldset v-if="user && user.is_vehikl_member" id="visibility-filters"
                                    v-model="visibilityFilter"/>


        <div class="week-grid px-4 py-6 gap-4">
            <v-modal :state="formModalState" @modal-closed="formModalState = 'closed'">
                <div class="flex flex-wrap flex-row-reverse overflow-visible relative">
                    <button class="bg-gray-900 absolute text-white border-4 border-gray-900 rounded-full px-4 py-1 -top-2 right-2 hover:text-gray-900 hover:bg-white x hover:border-gray-900" @click="formModalState = 'closed';">
                        <i aria-hidden="true" class="fa fa-times text-xl"></i> Close
                    </button>
                    <growth-session-form v-if="formModalState === 'open'"
                                         :growth-session="growthSessionToUpdate"
                                         :owner="user"
                                         :start-date="newGrowthSessionDate"
                                         class="growth-session-form"
                                         @submitted="onFormSubmitted"/>
                </div>
            </v-modal>
            <div v-for="date in growthSessions.weekDates"
                 :key="date.toDateString()"
                 :weekDay="date.weekDayString()"
                 :class="{
                     'bg-slate-100': date.isEvenDate(),
                     'bg-slate-50': !date.isEvenDate(),
                     }"
                 class="day flex flex-col mb-2 relative items-center bg-slate-100">

                <h2
                    class="text-3xl bg-white text-center tracking-wide text-slate-600 font-bold p-2 pt-4 pb-1 sticky sm:relative top-0 w-full z-20 rounded-t-xl"
                    v-text="date.weekDayString()"
                    :id="date.weekDayString()"
                ></h2>
                <p v-if="date.isInAPastDate()"
                class="text-center uppercase tracking-widest font-semibold text-lg px-4 py-1 bg-slate-200 text-slate-600 border-4 w-full">
                     Finished
                </p>
                <button
                    v-if="user && user.is_vehikl_member && ! date.isInAPastDate()"
                    class="create-growth-session block w-full tracking-widest uppercase font-semibold text-lg px-4 py-1 mb-2 bg-white border-gray-600 border-4 text-gray-600 hover:bg-gray-600 hover:border-gray-600 hover:text-white"
                    @click="onCreateNewGrowthSessionClicked(date)">
                    <i aria-hidden="true" class="fa fa-plus-circle mr-4"></i><span class="text">Add Session</span>
                </button>
                <div v-show="growthSessionsVisibleInDate(date).length === 0" class="text-gray-600 text-xl py-8 px-4 text-center">
                    <p v-text="`${Nothingator.random()}...`"/>
                    <p v-show="user && date.isToday()">Why don't you create the first one?</p>
                </div>
                <draggable :date="date"
                           item-key="id"
                           :list="growthSessionsVisibleInDate(date)"
                           class="w-full h-full py-2"
                           group="growth-sessions"
                           handle=".handle"
                           @change="onChange"
                           @end="onDragEnd">
                    <template #item="{ element: growthSession }">
                        <growth-session-card
                            :growth-session="growthSession"
                            :user="user"
                            @growth-session-updated="getAllGrowthSessionsOfTheWeek"
                            @copy-requested="onGrowthSessionCopyRequested"
                            @edit-requested="onGrowthSessionEditRequested"
                            @delete-requested="getAllGrowthSessionsOfTheWeek"/>
                    </template>
                </draggable>



            </div>
            <div class="block md:hidden fixed bottom-4 inset-x-4 z-50" v-if="growthSessions.hasCurrentDate">
                <button aria-label="Scroll to today"
                        class="flex w-full justify-center items-center px-2 py-1 border border-slate-300 shadow-sm text-xs font-medium rounded text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                        @click="scrollToDate(DateTime.today().weekDayString())">
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
