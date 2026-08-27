<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { WeekGrowthSessions } from '@/classes/WeekGrowthSessions';
import DayView from '@/components/legacy/DayView.vue';
import GrowthSessionForm from '@/components/legacy/GrowthSessionForm.vue';
import GrowthSessionTags from '@/components/legacy/GrowthSessionTags.vue';
import SessionDetailDrawer from '@/components/legacy/SessionDetailDrawer.vue';
import VisibilityRadioFieldset from '@/components/legacy/VisibilityRadioFieldset.vue';
import VModal from '@/components/legacy/VModal.vue';
import WeekView from '@/components/legacy/WeekView.vue';
import { useBoardUrlState, type BoardView } from '@/composables/useBoardUrlState';
import { filterSessions, type SessionFilterCriteria, type VisibilityFilter } from '@/lib/sessionFilters';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { TagsApi } from '@/services/TagsApi';
import { ITag, IUser } from '@/types';
import { useEcho } from '@laravel/echo-vue';
import { useMediaQuery, watchDebounced } from '@vueuse/core';
import { ChevronDown } from 'lucide-vue-next';
import { computed, onBeforeMount, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface IGrowthSessionCardDragChange {
    added?: { element: GrowthSession; index: number };
    removed?: { element: GrowthSession; index: number };
}

const props = defineProps<{ user?: IUser }>();
const { referenceDate, requestedView, sessionId, syncFromUrl, shiftDateBy, setView, setSessionId, watchUrl } = useBoardUrlState();
const growthSessions = ref<WeekGrowthSessions>(WeekGrowthSessions.empty());
const newGrowthSessionDate = ref('');
const growthSessionToUpdate = ref<GrowthSession | null>(null);
const draggedGrowthSession = ref<GrowthSession | null>(null);
const visibilityFilter = ref<VisibilityFilter>('all');
const formModalState = ref<'open' | 'closed'>('closed');
const selectedTagIds = ref<number[]>([]);
const searchQuery = ref('');
const debouncedSearchQuery = ref('');
const searchInput = ref<HTMLInputElement | null>(null);
const searchShortcutLabel = typeof navigator !== 'undefined' && /Mac|iPhone|iPad|iPod/.test(navigator.platform) ? '⌘K' : 'Ctrl K';

// The week view only makes sense on wider screens; small screens are day-only.
const isDesktop = useMediaQuery('(min-width: 768px)');
// On small screens there is nothing to switch to — the board is day-only.
const canSwitchView = computed(() => isDesktop.value);
const view = ref<BoardView>('day');

// The url's request wins whenever the url changes — on load, and on a history navigation...
watch(
    requestedView,
    (requested) => {
        view.value = isDesktop.value && requested === 'week' ? 'week' : 'day';
    },
    { immediate: true },
);

// ...but a screen too narrow for the week view demotes it, and it stays demoted: widening again
// must not yank the visitor back to a view they didn't re-ask for.
watch(isDesktop, (desktop) => {
    if (!desktop) view.value = 'day';
});

function selectView(selectedView: BoardView) {
    if (!canSwitchView.value) return;

    // Set the view outright rather than leaning on the watch above, which would not fire when the
    // url already asks for this view — the case after a demotion the url never heard about.
    view.value = selectedView;
    setView(selectedView);
}

const selectedSession = ref<GrowthSession | null>(null);

/**
 * Open or close the detail drawer, recording which session is open in the `?session=` query
 * parameter — so a link to a growth session (or any copied URL) lands on the board with that
 * session's detail already open, and the back button closes it again.
 */
function selectSession(growthSession: GrowthSession | null) {
    selectedSession.value = growthSession;
    setSessionId(growthSession?.id ?? null);
}

/** Adopt the session the URL names, if it is one this visitor can see in the loaded week. */
function adoptSessionFromUrl() {
    selectedSession.value = sessionId.value
        ? (growthSessions.value.allGrowthSessions.find((session) => session.id === sessionId.value) ?? null)
        : null;

    if (!selectedSession.value) return;

    const dayOfSession = growthSessions.value.weekDates.findIndex((date) => date.toDateString() === selectedSession.value!.date);
    if (dayOfSession >= 0) dayIndex.value = dayOfSession;
}

watch(
    () => props.user?.id,
    () => {
        selectSession(null);

        if (props.user) return;

        loadWeekFromUrl();
    },
);

const dayIndex = ref(0);
const filtersOpen = ref(false);

const allTags = ref<ITag[]>([]);

async function getAllTags() {
    try {
        allTags.value = await TagsApi.index();
    } catch {
        /* leave the filter empty if the tag list can't be loaded */
    }
}

const weekLabel = computed(() => {
    if (growthSessions.value.weekDates.length === 0) return '';
    return `${growthSessions.value.firstDay.format('MMM D')} – ${growthSessions.value.lastDay.format('MMM D')}`.toUpperCase();
});

const selectedDate = computed(() => growthSessions.value.weekDates[dayIndex.value] ?? growthSessions.value.weekDates[0] ?? DateTime.today());

const filterCriteria = computed<SessionFilterCriteria>(() => ({
    user: props.user,
    tagIds: selectedTagIds.value,
    visibility: visibilityFilter.value,
    searchQuery: debouncedSearchQuery.value,
}));

function growthSessionsVisibleInDate(date: DateTime): GrowthSession[] {
    return filterSessions(growthSessions.value.getSessionByDate(date), filterCriteria.value);
}

const daySessions = computed(() => growthSessionsVisibleInDate(selectedDate.value));

// Precompute the filtered sessions for each day so the dumb WeekView renders from props alone.
const sessionsByDate = computed<Record<string, GrowthSession[]>>(() => {
    const map: Record<string, GrowthSession[]> = {};
    for (const date of growthSessions.value.weekDates) {
        map[date.toDateString()] = growthSessionsVisibleInDate(date);
    }
    return map;
});

watchDebounced(
    searchQuery,
    (newValue) => {
        debouncedSearchQuery.value = newValue;
    },
    { debounce: 300 },
);

// Registered before the first await, so unmounting mid-load still tears the listener down.
const stopWatchingUrl = watchUrl(loadWeekFromUrl);

onBeforeMount(async () => {
    await getAllTags();
    syncFromUrl();
    await loadWeekFromUrl();
    // A deep-linked session has already chosen the day it lives on; otherwise start on today.
    if (!selectedSession.value) {
        const todayIdx = growthSessions.value.weekDates.findIndex((d) => d.isToday());
        dayIndex.value = todayIdx >= 0 ? todayIdx : 0;
    }
});

onMounted(() => {
    window.addEventListener('gs:create-session', handleHeaderCreate);
    window.addEventListener('gs:focus-search', handleSearchFocus);
    window.addEventListener('keydown', handleViewShortcut);
});

onBeforeUnmount(() => {
    stopWatchingUrl();
    window.removeEventListener('gs:create-session', handleHeaderCreate);
    window.removeEventListener('gs:focus-search', handleSearchFocus);
    window.removeEventListener('keydown', handleViewShortcut);
});

function handleHeaderCreate() {
    onCreateNewGrowthSessionClicked(selectedDate.value);
}

function handleSearchFocus() {
    searchInput.value?.focus();
    searchInput.value?.select();
}

function handleSearchEscape() {
    searchInput.value?.blur();
}

function handleViewShortcut(event: KeyboardEvent) {
    if (!canSwitchView.value) return;
    if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;

    const target = event.target;
    if (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement ||
        (target instanceof HTMLElement && target.isContentEditable)
    ) {
        return;
    }

    const key = event.key.toLowerCase();
    if (key === 'd') selectView('day');
    if (key === 'w') selectView('week');
}

/** Fetch the week the URL asks for, then re-open whichever session it names. Ordering matters: the drawer can only adopt a session the week has loaded. */
async function loadWeekFromUrl() {
    await getAllGrowthSessionsOfTheWeek();
    adoptSessionFromUrl();
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
    selectSession(null);
    growthSessionToUpdate.value = growthSession;
    newGrowthSessionDate.value = '';
    formModalState.value = 'open';
}

function onGrowthSessionCopyRequested(growthSession: GrowthSession) {
    selectSession(null);
    growthSessionToUpdate.value = new GrowthSession({ ...growthSession, id: 0 });
    newGrowthSessionDate.value = '';
    formModalState.value = 'open';
}

async function changeReferenceDate(deltaDays: number) {
    // The open session belongs to the week being left behind, so it closes with the move.
    // `shiftDateBy` drops it from the URL in the same history entry.
    selectedSession.value = null;
    shiftDateBy(deltaDays);
    await getAllGrowthSessionsOfTheWeek();
}

function onTagClick(id: number) {
    if (selectedTagIds.value.includes(id)) {
        selectedTagIds.value = selectedTagIds.value.filter((tagId) => tagId != id);
    } else {
        selectedTagIds.value.push(id);
    }
}

async function onDrawerRefresh() {
    const id = selectedSession.value?.id;
    await getAllGrowthSessionsOfTheWeek();
    if (id) {
        selectSession(growthSessions.value.allGrowthSessions.find((s) => s.id === id) ?? null);
    }
}

async function onDaySessionJoin(session: GrowthSession) {
    await session.join();
    await onDrawerRefresh();
}

async function onDaySessionWatch(session: GrowthSession) {
    await session.watch();
    await onDrawerRefresh();
}

async function onDaySessionLeave(session: GrowthSession) {
    await session.leave();
    await onDrawerRefresh();
}

async function onGrowthSessionDeleteRequested(session: GrowthSession) {
    if (!confirm('Are you sure you want to delete?')) return;

    try {
        await session.delete();
    } catch (error: any) {
        if (error.response?.status !== 404) {
            console.error('Failed to delete growth session:', error);
        }
    } finally {
        selectSession(null);
        await getAllGrowthSessionsOfTheWeek();
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
    <div v-if="growthSessions.isReady" class="gs-page flex flex-1 flex-col">
        <!-- Control bar -->
        <div class="gs-bar gs-border relative grid grid-cols-2 items-center gap-3 border-b px-5 py-4 sm:px-7 md:flex md:flex-wrap md:gap-3.5">
            <div class="col-span-2 flex w-full flex-none items-center justify-between gap-2.5 md:col-span-1 md:w-auto md:justify-start">
                <button
                    aria-label="Load previous week"
                    class="load-previous-week gs-seg gs-text-strong transition-smooth hover:text-gs-accent flex h-8 w-8 cursor-pointer items-center justify-center rounded-md text-xl leading-none"
                    @click="changeReferenceDate(-7)"
                >
                    ‹
                </button>
                <span class="gs-text-strong text-sm font-semibold whitespace-nowrap">{{ weekLabel }}</span>
                <button
                    aria-label="Load next week"
                    class="load-next-week gs-seg gs-text-strong transition-smooth hover:text-gs-accent flex h-8 w-8 cursor-pointer items-center justify-center rounded-md text-xl leading-none"
                    @click="changeReferenceDate(7)"
                >
                    ›
                </button>
            </div>

            <div
                class="gs-seg hidden flex-none rounded-lg p-0.75 md:flex"
                :title="canSwitchView ? undefined : 'Week view is only available on larger screens'"
            >
                <button
                    type="button"
                    aria-keyshortcuts="D"
                    :disabled="!canSwitchView"
                    class="transition-smooth rounded-md px-4 py-2 text-sm font-semibold whitespace-nowrap"
                    :class="[
                        view === 'day' ? 'gs-header-bg text-white' : 'gs-text-sub',
                        canSwitchView ? 'cursor-pointer' : 'cursor-not-allowed opacity-50',
                    ]"
                    @click="selectView('day')"
                >
                    Day
                </button>
                <button
                    type="button"
                    aria-keyshortcuts="W"
                    :disabled="!canSwitchView"
                    class="transition-smooth rounded-md px-4 py-2 text-sm font-semibold whitespace-nowrap"
                    :class="[
                        view === 'week' ? 'gs-header-bg text-white' : 'gs-text-sub',
                        canSwitchView ? 'cursor-pointer' : 'cursor-not-allowed opacity-50',
                    ]"
                    @click="selectView('week')"
                >
                    Week
                </button>
            </div>

            <div class="relative col-span-2 min-w-0 md:col-span-1 md:min-w-45 md:flex-1">
                <input
                    ref="searchInput"
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search sessions..."
                    class="search-input gs-input w-full rounded-lg py-2.5 pr-18 pl-4 text-sm"
                    aria-label="Search growth sessions by title, description, or host name"
                    @keydown.esc="handleSearchEscape"
                />
                <kbd
                    v-if="!searchQuery"
                    class="gs-text-muted gs-border pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 rounded border px-1.5 py-0.5 font-sans text-[10px]"
                >
                    {{ searchShortcutLabel }}
                </kbd>
                <button
                    v-if="searchQuery"
                    class="gs-text-muted transition-smooth hover:text-gs-accent absolute top-1/2 right-2 -translate-y-1/2 cursor-pointer rounded-md px-2 py-1"
                    aria-label="Clear search"
                    @click="searchQuery = ''"
                >
                    ✕
                </button>
            </div>

            <button
                v-if="allTags.length"
                type="button"
                class="gs-seg gs-text-strong transition-smooth flex w-full flex-none cursor-pointer items-center justify-between gap-3 rounded-lg px-4 py-2.5 text-sm font-semibold whitespace-nowrap md:w-auto md:justify-start"
                @click="filtersOpen = !filtersOpen"
            >
                <span class="flex items-center gap-1.5">
                    Filters
                    <span
                        v-if="selectedTagIds.length"
                        class="gs-header-bg inline-flex h-4.5 min-w-4.5 items-center justify-center rounded-full px-1 text-xs font-bold text-white"
                        >{{ selectedTagIds.length }}</span
                    >
                </span>
                <ChevronDown class="transition-transform duration-200" :class="{ 'rotate-180': filtersOpen }" :size="16" aria-hidden="true" />
            </button>

            <VisibilityRadioFieldset
                v-if="user && user.is_vehikl_member"
                id="visibility-filters"
                v-model="visibilityFilter"
                class="justify-self-end md:ml-auto"
            />
        </div>

        <!-- Filters panel -->
        <div v-show="filtersOpen" class="gs-col gs-border w-full border-b px-5 py-4 sm:px-7">
            <div class="mb-3 flex items-center justify-between">
                <span class="gs-text-sub text-xs font-bold tracking-[0.04em]">FILTER BY TAG</span>
                <button type="button" class="gs-accent-text cursor-pointer text-sm font-semibold" @click="selectedTagIds = []">Clear</button>
            </div>
            <GrowthSessionTags ref="growthSessionTags" :tags="allTags" :selected-tag-ids="selectedTagIds" class="flex-wrap" @tag-click="onTagClick" />
        </div>

        <!-- Week view -->
        <WeekView
            v-show="view === 'week'"
            :week-dates="growthSessions.weekDates"
            :sessions-by-date="sessionsByDate"
            :user="user"
            @create="onCreateNewGrowthSessionClicked"
            @edit-requested="onGrowthSessionEditRequested"
            @copy-requested="onGrowthSessionCopyRequested"
            @open-detail="selectSession"
            @refresh="getAllGrowthSessionsOfTheWeek"
            @drag-change="onChange"
            @drag-end="onDragEnd"
        />

        <!-- Day view -->
        <DayView
            v-show="view === 'day'"
            :days="growthSessions.weekDates"
            :selected-index="dayIndex"
            :sessions="daySessions"
            :current-label="selectedDate.weekDayString()"
            :user="user"
            @select-day="dayIndex = $event"
            @open-detail="selectSession"
            @join="onDaySessionJoin"
            @watch="onDaySessionWatch"
            @leave="onDaySessionLeave"
            @edit-requested="onGrowthSessionEditRequested"
            @delete-requested="onGrowthSessionDeleteRequested"
            @copy-requested="onGrowthSessionCopyRequested"
            @create="onCreateNewGrowthSessionClicked(selectedDate)"
        />

        <!-- Create / edit modal -->
        <v-modal :state="formModalState" @modal-closed="formModalState = 'closed'">
            <div class="gs-card relative rounded-2xl p-6">
                <button
                    class="gs-text-muted transition-smooth hover:text-gs-accent absolute top-5 right-5 cursor-pointer text-xs font-semibold tracking-[0.04em]"
                    @click="formModalState = 'closed'"
                >
                    CLOSE ✕
                </button>
                <growth-session-form
                    v-if="formModalState === 'open' && user"
                    :growth-session="growthSessionToUpdate"
                    :owner="user"
                    :start-date="newGrowthSessionDate"
                    class="growth-session-form"
                    @submitted="onFormSubmitted"
                />
            </div>
        </v-modal>

        <!-- The transition keeps the drawer mounted while it slides back out; it styles its own gs-drawer-* classes. -->
        <!-- :key forces a remount on session switch, resetting the drawer's local state (draft comment, copy status, focus trap) -->
        <Transition name="gs-drawer" mode="out-in">
            <SessionDetailDrawer
                v-if="selectedSession"
                :key="selectedSession.id"
                :growth-session="selectedSession"
                :user="user"
                @close="selectSession(null)"
                @edit-requested="onGrowthSessionEditRequested"
                @delete-requested="onGrowthSessionDeleteRequested"
                @refresh="onDrawerRefresh"
            />
        </Transition>
    </div>
</template>
