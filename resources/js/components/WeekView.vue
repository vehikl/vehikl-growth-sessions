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
        <div class="flex flex-col justify-center items-center text-xl text-blue-600 font-bold">
            <VisibilityRadioFieldset v-if="user && user.is_vehikl_member" id="visibility-filters"
                                     v-model="visibilityFilter"/>
            <div class="flex my-5">
                <button aria-label="Load previous week"
                        class="load-previous-week mx-4 mb-2"
                        @click="changeReferenceDate(-7)">
                    <i aria-hidden="true" class="fa fa-chevron-left"></i>
                </button>
                <h2 v-if="growthSessions.weekDates.length > 0" class="text-center mb-2 w-72">
                    Week of
                    {{ growthSessions.firstDay.format("MMM-DD") }} to
                    {{ growthSessions.lastDay.format("MMM-DD") }}
                </h2>
                <button aria-label="Load next week"
                        class="load-next-week mx-4 mb-2"
                        @click="changeReferenceDate(+7)">
                    <i aria-hidden="true" class="fa fa-chevron-right"></i>
                </button>
            </div>
        </div>


        <div class="week-grid p-6">
            <v-modal :state="formModalState" @modal-closed="formModalState = 'closed'">
                <div class="flex flex-wrap flex-row-reverse w-full h-full overflow-y-scroll">
                    <button class="p-4 pb-0" @click="formModalState = 'closed';">
                        <i aria-hidden="true" class="fa fa-times text-xl"></i>
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
                 class="day flex flex-col mx-1 mb-2 relative items-center">

                <h3
                    class="text-3xl tracking-wide text-white font-bold p-3 sticky sm:relative top-0 w-full z-20 rounded-br-full"
                    v-text="date.weekDayString()"
                    :id="date.weekDayString()"
                    :class="{
                     'bg-blue-900 border-blue-200': date.isEvenDate(),
                     'bg-blue-800 border-blue-200': !date.isEvenDate(),
                     }"
                ></h3>
                <div v-show="growthSessionsVisibleInDate(date).length === 0" class="text-blue-600 text-xl py-8">
                    <p v-text="`${Nothingator.random()}...`"/>
                    <p v-show="user && date.isToday()">Why don't you create the first one?</p>
                </div>
                <draggable :date="date"
                           item-key="id"
                           :list="growthSessionsVisibleInDate(date)"
                           class="w-full py-2 overflow-y-auto"
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

                <button
                    v-if="user && user.is_vehikl_member && ! date.isInAPastDate()"
                    class="create-growth-session flex flex-col items-center text-6xl tracking-wide px-1 py-12 w-full bg-white text-blue-800 hover:bg-white hover:text-sky-500"
                    @click="onCreateNewGrowthSessionClicked(date)">
                    <i aria-hidden="true" class="fa fa-plus-circle"></i><span class="text-2xl mt-2">Add Session</span>
                </button>

            </div>
            <div class="block md:hidden fixed bottom-0 right-0 m-2 z-50" v-if="growthSessions.hasCurrentDate">
                <button aria-label="Scroll to today"
                        class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    @media (min-width: 768px) {
        grid-auto-rows: 800px;
    }
    @media (max-width: 767px) {
        grid-auto-rows: auto;
    }
    grid-gap: 1rem;
}
</style>
