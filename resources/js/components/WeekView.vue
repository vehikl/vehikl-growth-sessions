<template>
    <div v-if="growthSessions.isReady">
        <label>
            All
            <input v-model="visibilityFilter" name="filter-sessions" type="radio" value="all">
        </label>
        <label>
            Private Only
            <input id="private" v-model="visibilityFilter" name="filter-sessions" type="radio" value="private">
        </label>


        <div class="flex justify-center items-center text-xl text-blue-600 font-bold">
            <button aria-label="Load previous week"
                    class="load-previous-week mx-4 mb-2"
                    @click="changeReferenceDate(-7)">
                <i aria-hidden="true" class="fa fa-chevron-left"></i>
            </button>
            <h2 v-if="growthSessions.weekDates.length > 0" class="text-center mb-2 w-72">
                Week of
                {{ growthSessions.firstDay.format('MMM-DD') }} to
                {{ growthSessions.lastDay.format('MMM-DD') }}
            </h2>
            <button ref="load-next-week-button"
                    aria-label="Load next week"
                    class="load-next-week mx-4 mb-2"
                    @click="changeReferenceDate(+7)">
                <i aria-hidden="true" class="fa fa-chevron-right"></i>
            </button>
        </div>

        <div class="flex flex-col md:flex-row justify-center flex-wrap">
            <modal :clickToClose="false" :dynamic="true" :height="650" :width="500" name="growth-session-form">
               <div class="flex flex-wrap flex-row-reverse w-full h-full overflow-y-scroll">
                   <button class="p-4 pb-0" @click="$modal.hide('growth-session-form')">
                       <i class="fa fa-times text-xl" aria-hidden="true"></i>
                   </button>
                   <growth-session-form :growth-session="growthSessionToUpdate"
                                        :owner="user"
                                        :start-date="newGrowthSessionDate"
                                        class="growth-session-form"
                                        @submitted="onFormSubmitted"/>
               </div>
            </modal>
            <div v-for="date in growthSessions.weekDates"
                 :key="date.toDateString()"
                 :class="{
                 'bg-blue-100 border-blue-200': date.isEvenDate(),
                 'bg-blue-200 border-blue-300': !date.isEvenDate(),
                 }"

                 :weekDay="date.weekDayString()"
                 class="day flex flex-col text-center mx-1 mb-2 relative rounded border">
                <h3
                    class="text-lg text-blue-700 font-bold p-3 md:pt-6 sticky sm:relative top-0 w-full z-20 border-b md:border-b-0 rounded-t md:rounded-none mb-2 md:mb-0"
                    v-text="date.weekDayString()"
                    :id="date.weekDayString()"
                    :class="{
                     'bg-blue-100 border-blue-200': date.isEvenDate(),
                     'bg-blue-200 border-blue-300': !date.isEvenDate(),
                     }"
                ></h3>
                <div v-show="growthSessionsVisibleInDate(date).length === 0" class="text-blue-600 text-lg my-4">
                    <p v-text="`${Nothingator.random()}...`"/>
                    <p v-show="user && date.isToday()">Why don't you create the first one?</p>
                </div>

                <draggable :date="date"
                           :list="growthSessionsVisibleInDate(date)"
                           class="h-full w-full px-2"
                           group="growth-sessions"
                           handle=".handle"
                           @change="onChange"
                           @end="onDragEnd">
                    <div v-for="growthSession in growthSessionsVisibleInDate(date)"
                         :key="growthSession.id">
                        <growth-session-card
                            :growth-session="growthSession"
                            :user="user"
                            class="mb-3 transform transition-transform duration-150"
                            @growth-session-updated="getAllGrowthSessionsOfTheWeek"
                            @copy-requested="onGrowthSessionCopyRequested"
                            @edit-requested="onGrowthSessionEditRequested"
                            @delete-requested="getAllGrowthSessionsOfTheWeek"/>
                    </div>

                    <button
                        v-if="user && user.is_vehikl_member && ! date.isInAPastDate()"
                        class="create-growth-session text-5xl h-20 w-20 text-blue-600 hover:text-blue-700 focus:text-blue-700 font-bold my-3"
                        @click="onCreateNewGrowthSessionClicked(date)">
                        <i aria-hidden="true" class="fa fa-plus-circle"></i>
                    </button>
                </draggable>
            </div>
            <div class="block md:hidden fixed bottom-0 right-0 m-2 z-50" v-if="growthSessions.hasCurrentDate">
                <button @click="scrollToDate(DateTime.today().weekDayString())" type="button" ref="scroll-to-today" class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Go to today <i aria-hidden="true" class="fa fa-calendar ml-2"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import {Component, Prop, Vue} from 'vue-property-decorator';
import {IUser} from '../types';
import GrowthSessionCard from './GrowthSessionCard.vue';
import GrowthSessionForm from './GrowthSessionForm.vue';
import {GrowthSessionApi} from '../services/GrowthSessionApi';
import {DateTime} from '../classes/DateTime';
import Draggable from 'vuedraggable';
import {GrowthSession} from '../classes/GrowthSession';
import {WeekGrowthSessions} from '../classes/WeekGrowthSessions';
import {Nothingator} from '../classes/Nothingator';

interface IGrowthSessionCardDragChange {
    added?: { element: GrowthSession, index: number }
    removed?: { element: GrowthSession, index: number }
}

@Component({
    components: {GrowthSessionForm, GrowthSessionCard, Draggable}
})
export default class WeekView extends Vue {
    @Prop({required: false, default: null}) user!: IUser;
    referenceDate: DateTime = DateTime.today();
    growthSessions: WeekGrowthSessions = WeekGrowthSessions.empty();
    newGrowthSessionDate: string = '';
    growthSessionToUpdate: GrowthSession | null = null;
    DateTime = DateTime;
    Nothingator = Nothingator;
    draggedGrowthSession!: GrowthSession;
    visibilityFilter: string = 'all'

    async created() {
        await this.refreshGrowthSessionsOfTheWeek()
        window.onpopstate = this.refreshGrowthSessionsOfTheWeek;
    }

    beforeDestroy() {
        window.onpopstate = null;
    }

    async refreshGrowthSessionsOfTheWeek() {
        this.useDateFromUrlAsReference();
        await this.getAllGrowthSessionsOfTheWeek();
    }

    growthSessionsVisibleInDate(date: DateTime) {
        debugger
        let todaysGrowthSessions = this.growthSessions.getSessionByDate(date);
        if (this.visibilityFilter == 'all')
            return todaysGrowthSessions

        return todaysGrowthSessions.filter((session) => {
            return this.visibilityFilter === 'private' && !session.is_public
        })
    }

    useDateFromUrlAsReference() {
        const urlSearchParams = new URLSearchParams(window.location.search);
        this.referenceDate = urlSearchParams.has('date')
            ? DateTime.parseByDate(urlSearchParams.get('date')!)
            : DateTime.today();
    }

    async onDragEnd(location: any) {
        let targetDate = location.to.__vue__.$attrs.date;
        try {
            await GrowthSessionApi.update(this.draggedGrowthSession, {date: targetDate.toDateString(), attendee_limit: this.draggedGrowthSession.attendee_limit});
        } catch (e) {
        }

        await this.getAllGrowthSessionsOfTheWeek();
    }

    onChange(change: IGrowthSessionCardDragChange) {
        if (change.added) {
            return this.draggedGrowthSession = change.added.element;
        }
    }


    async getAllGrowthSessionsOfTheWeek() {
        this.growthSessions = await GrowthSessionApi.getAllGrowthSessionsOfTheWeek(this.referenceDate.toDateString());
    }

    async onFormSubmitted() {
        await this.getAllGrowthSessionsOfTheWeek();
        this.$modal.hide('growth-session-form');
    }

    onCreateNewGrowthSessionClicked(startDate: DateTime) {
        this.growthSessionToUpdate = null;
        this.newGrowthSessionDate = startDate.toISOString();
        this.$modal.show('growth-session-form');
    }

    onGrowthSessionEditRequested(growthSession: GrowthSession) {
        this.growthSessionToUpdate = growthSession;
        this.newGrowthSessionDate = '';
        this.$modal.show('growth-session-form');
    }

    onGrowthSessionCopyRequested(growthSession: GrowthSession) {
        growthSession.id = 0;
        this.growthSessionToUpdate = growthSession;
        this.newGrowthSessionDate = '';
        this.$modal.show('growth-session-form');
    }

    async changeReferenceDate(deltaDays: number) {
        this.referenceDate.addDays(deltaDays);
        window.history.pushState({}, document.title, `?date=${this.referenceDate.toDateString()}`);
        await this.getAllGrowthSessionsOfTheWeek();
    }

    scrollToDate(id: string) {
        window.document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
    }
}
</script>

<style lang="scss" scoped>
.day {

    min-height: 10rem;
    @media (min-width: 768px) {
        min-height: 35rem;
        width: 20rem;
    }
}
</style>
