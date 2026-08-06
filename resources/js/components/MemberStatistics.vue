<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import {
    DateRange,
    decodeSettings,
    encodeSettings,
    filterMembers,
    formatRangeLabel,
    lastMonthRange,
    lastWeekRange,
    paginate,
    SortableField,
    SortDirection,
    sortMembers,
    StatisticsSettings,
    suggestMembers,
    totalPages,
} from '@/lib/statistics';
import { IUserStatistics } from '@/types';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

interface IProps {
    members: IUserStatistics[];
    startDate: string;
    endDate: string;
    firstSessionDate: string;
    isLoading?: boolean;
}

const props = defineProps<IProps>();
const emit = defineEmits<{ (event: 'rangeChange', range: DateRange): void }>();

const PER_PAGE = 25;
const SUGGESTION_LIMIT = 8;
const FILTER_STORAGE_KEY = 'statistics_filter';
const DEFAULT_SETTINGS: StatisticsSettings = { list: [], shouldUseList: false };

/**
 * No column is hidden on narrow screens. Every count here is the point of the table and
 * has nowhere else to be read, so a phone gets a table that scrolls sideways inside its
 * own container rather than one that quietly drops the watched count.
 */
const COLUMNS: { label: string; field: SortableField; numeric: boolean }[] = [
    { label: 'Name', field: 'name', numeric: false },
    { label: 'Hosted', field: 'sessions_hosted_count', numeric: true },
    { label: 'Attended', field: 'sessions_attended_count', numeric: true },
    { label: 'Watched', field: 'sessions_watched_count', numeric: true },
    { label: 'Total', field: 'total_sessions_count', numeric: true },
    { label: 'Yet to mob with', field: 'has_not_mobbed_with_count', numeric: true },
];

/**
 * A shared link wins over whatever this browser last saved, so the recipient sees the
 * sender's cohort rather than their own. Anything unreadable falls back to defaults
 * rather than leaving the section broken.
 */
function loadSettings(): StatisticsSettings {
    const shared = new URLSearchParams(window.location.search).get('settings');
    const fromUrl = shared ? decodeSettings(shared) : null;

    if (fromUrl) {
        return fromUrl;
    }

    try {
        const stored = localStorage.getItem(FILTER_STORAGE_KEY);
        const parsed = stored ? JSON.parse(stored) : null;

        if (Array.isArray(parsed?.list) && typeof parsed?.shouldUseList === 'boolean') {
            return { list: parsed.list, shouldUseList: parsed.shouldUseList };
        }
    } catch (error) {
        console.error('Could not read saved statistics filters:', error);
    }

    return { ...DEFAULT_SETTINGS };
}

const initialSettings = loadSettings();

const filter = reactive<StatisticsSettings>({
    list: initialSettings.list,
    shouldUseList: initialSettings.shouldUseList,
});

const search = ref('');
const isSearchOpen = ref(false);
const highlighted = ref(0);
const isRangeOpen = ref(false);
const searchField = ref<HTMLElement | null>(null);
const rangeField = ref<HTMLElement | null>(null);

const sort = reactive<{ field: SortableField; direction: SortDirection }>({ field: 'name', direction: 'asc' });
const range = reactive<DateRange>({ startDate: props.startDate, endDate: props.endDate });
const page = ref(1);
const openMember = ref<IUserStatistics | null>(null);
const shareResult = ref<'copied' | 'failed' | null>(null);
let shareResultTimer: ReturnType<typeof setTimeout>;

function closeOnEscape(event: KeyboardEvent): void {
    if (event.key !== 'Escape') {
        return;
    }

    openMember.value = null;
    isSearchOpen.value = false;
    isRangeOpen.value = false;
}

/**
 * Both overlays close on a press that lands outside them rather than on a click, so a
 * drag that starts in the suggestions and ends outside doesn't count as leaving.
 */
function closeOnOutsidePress(event: MouseEvent): void {
    const target = event.target as Node | null;

    if (target && !searchField.value?.contains(target)) {
        isSearchOpen.value = false;
    }

    if (target && !rangeField.value?.contains(target)) {
        isRangeOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('mousedown', closeOnOutsidePress);
});
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('mousedown', closeOnOutsidePress);
    clearTimeout(shareResultTimer);
});

const matchingMembers = computed(() => sortMembers(filterMembers(props.members, filter), sort.field, sort.direction));
const suggestions = computed(() => suggestMembers(props.members, search.value, filter.list, SUGGESTION_LIMIT));
const rangeLabel = computed(() => formatRangeLabel(range));
const pageCount = computed(() => totalPages(matchingMembers.value.length, PER_PAGE));
const rows = computed(() => paginate(matchingMembers.value, page.value, PER_PAGE));

const showingSummary = computed(() => {
    const total = matchingMembers.value.length;

    if (total === 0) {
        return '0 members';
    }

    const firstRow = (page.value - 1) * PER_PAGE + 1;

    return `Showing ${firstRow}–${Math.min(firstRow + PER_PAGE - 1, total)} of ${total}`;
});

watch([() => filter.list, () => filter.shouldUseList], () => (page.value = 1), { deep: true });
watch(search, () => (highlighted.value = 0));
watch(pageCount, (count) => (page.value = Math.min(page.value, count)));

watch(
    [() => filter.list, () => filter.shouldUseList],
    () => {
        try {
            localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify({ list: filter.list, shouldUseList: filter.shouldUseList }));
        } catch (error) {
            console.error('Could not save statistics filters:', error);
        }
    },
    { deep: true },
);

// The server can resolve a range differently to what was asked for, so props stay the
// source of truth and the pickers follow them.
watch(
    () => [props.startDate, props.endDate],
    ([startDate, endDate]) => {
        range.startDate = startDate;
        range.endDate = endDate;
    },
);

watch(
    () => [range.startDate, range.endDate],
    ([startDate, endDate]) => {
        if (startDate && endDate && (startDate !== props.startDate || endDate !== props.endDate)) {
            emit('rangeChange', { startDate, endDate });
        }
    },
);

function applyRange({ startDate, endDate }: DateRange): void {
    range.startDate = startDate;
    range.endDate = endDate;
}

function sortBy(field: SortableField): void {
    if (sort.field === field) {
        sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
        return;
    }

    sort.field = field;
    sort.direction = 'asc';
}

function ariaSortFor(field: SortableField): 'ascending' | 'descending' | 'none' {
    if (sort.field !== field) {
        return 'none';
    }

    return sort.direction === 'asc' ? 'ascending' : 'descending';
}

/**
 * Picking a member saves them to the list; it never narrows the table on its own, so the
 * numbers on screen only ever change when the checkbox below says they should.
 */
function chooseMember(name: string): void {
    if (!filter.list.includes(name)) {
        filter.list = [...filter.list, name];
    }

    search.value = '';
    isSearchOpen.value = false;
}

function chooseHighlightedMember(): void {
    const candidate = suggestions.value[highlighted.value];

    if (isSearchOpen.value && candidate) {
        chooseMember(candidate.name);
    }
}

/** Wraps at both ends, so holding one arrow key cycles rather than dead-ending. */
function moveHighlight(step: number): void {
    isSearchOpen.value = true;

    if (suggestions.value.length > 0) {
        highlighted.value = (highlighted.value + step + suggestions.value.length) % suggestions.value.length;
    }
}

function removeNameFromList(name: string): void {
    filter.list = filter.list.filter((listedName) => listedName !== name);
}

/** Also drops the shared settings from the address bar, so a reload doesn't restore them. */
function clearList(): void {
    filter.list = [];

    const url = new URL(window.location.href);

    if (url.searchParams.has('settings')) {
        url.searchParams.delete('settings');
        window.history.replaceState(window.history.state, '', url.toString());
    }
}

/**
 * The address bar is moved to the shareable link before anything is copied, so the link
 * exists whether or not the copy lands — which is what makes the failure message's advice
 * to copy from the address bar true. Inertia's own history state is carried over, so back
 * and forward still restore this page.
 */
async function copyShareableUrl(): Promise<void> {
    const url = new URL(window.location.href);
    url.searchParams.set('settings', encodeSettings({ list: filter.list, shouldUseList: filter.shouldUseList }));
    url.searchParams.set('start_date', range.startDate);
    url.searchParams.set('end_date', range.endDate);

    const shareableUrl = url.toString();
    window.history.replaceState(window.history.state, '', shareableUrl);

    shareResult.value = (await copyToClipboard(shareableUrl)) ? 'copied' : 'failed';

    clearTimeout(shareResultTimer);
    shareResultTimer = setTimeout(() => (shareResult.value = null), 5000);
}

/**
 * `navigator.clipboard` is missing entirely outside a secure context, which is every
 * plain-http development host, so the deprecated selection copy is the fallback rather
 * than an immediate failure.
 */
async function copyToClipboard(text: string): Promise<boolean> {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch (error) {
        console.error('Could not copy the shareable URL to the clipboard:', error);
    }

    return copyBySelection(text);
}

function copyBySelection(text: string): boolean {
    const field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.top = '0';
    field.style.opacity = '0';
    document.body.appendChild(field);

    try {
        field.select();

        return document.execCommand('copy');
    } catch (error) {
        console.error('Could not copy the shareable URL by selection:', error);
        return false;
    } finally {
        field.remove();
    }
}
</script>

<template>
    <section class="gs-card gs-border rounded-xl border p-6 shadow-sm" aria-labelledby="member-statistics-heading">
        <header class="mb-5 flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="member-statistics-heading" class="gs-text-strong text-sm font-bold tracking-[0.05em] uppercase">Everyone's numbers</h2>
        </header>

        <div class="gs-secondary-bg mb-5 flex flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-wrap items-start gap-4">
                <div ref="searchField" class="relative min-w-64 flex-1">
                    <label for="filter-by-name" class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.06em] uppercase">
                        Find &amp; save people
                    </label>
                    <input
                        id="filter-by-name"
                        v-model="search"
                        name="filter-by-name"
                        type="text"
                        role="combobox"
                        autocomplete="off"
                        aria-controls="member-suggestions"
                        :aria-expanded="isSearchOpen"
                        :aria-activedescendant="isSearchOpen && suggestions[highlighted] ? `member-suggestion-${highlighted}` : undefined"
                        placeholder="Search members"
                        class="gs-input gs-text-input w-full rounded-lg px-3 py-2.5 text-sm"
                        @focus="isSearchOpen = true"
                        @keydown.down.prevent="moveHighlight(1)"
                        @keydown.up.prevent="moveHighlight(-1)"
                        @keydown.enter.prevent="chooseHighlightedMember"
                    />

                    <ul
                        v-show="isSearchOpen"
                        id="member-suggestions"
                        role="listbox"
                        aria-label="Matching members"
                        class="gs-card gs-border absolute z-20 mt-2 max-h-64 w-full overflow-y-auto rounded-xl border py-1 shadow-lg"
                    >
                        <li
                            v-for="(candidate, index) in suggestions"
                            :id="`member-suggestion-${index}`"
                            :key="candidate.user_id"
                            role="option"
                            :aria-selected="index === highlighted"
                        >
                            <button
                                type="button"
                                :class="[
                                    'w-full cursor-pointer px-4 py-2.5 text-left text-sm',
                                    index === highlighted ? 'gs-secondary-bg gs-text-strong font-semibold' : 'gs-text-body',
                                ]"
                                @mousemove="highlighted = index"
                                @click="chooseMember(candidate.name)"
                            >
                                {{ candidate.name }}
                            </button>
                        </li>
                        <li v-if="suggestions.length === 0" class="gs-text-muted px-4 py-2.5 text-sm">No members match</li>
                    </ul>

                    <ul v-if="filter.list.length > 0" class="mt-3 flex flex-wrap gap-2" aria-label="Saved filter list">
                        <li
                            v-for="listedName in filter.list"
                            :key="listedName"
                            class="gs-card gs-border flex items-center gap-2 rounded-full border py-1.5 pr-2 pl-4 text-sm"
                        >
                            <span class="gs-text-strong font-semibold">{{ listedName }}</span>
                            <button
                                type="button"
                                class="gs-text-muted cursor-pointer rounded-full px-1.5 leading-none hover:text-red-600"
                                :aria-label="`Remove ${listedName} from the list`"
                                @click="removeNameFromList(listedName)"
                            >
                                ×
                            </button>
                        </li>
                    </ul>
                </div>

                <div ref="rangeField" class="relative">
                    <span id="date-range-label" class="gs-text-sub mb-1.5 block text-xs font-bold tracking-[0.06em] uppercase">Date range</span>
                    <button
                        type="button"
                        class="gs-card gs-border gs-text-strong flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-bold whitespace-nowrap"
                        aria-haspopup="dialog"
                        :aria-expanded="isRangeOpen"
                        :aria-label="`Date range: ${rangeLabel}`"
                        @click="isRangeOpen = !isRangeOpen"
                    >
                        <i class="fa fa-calendar gs-text-muted" aria-hidden="true"></i>
                        {{ rangeLabel }}
                    </button>

                    <div
                        v-show="isRangeOpen"
                        role="dialog"
                        aria-labelledby="date-range-label"
                        class="gs-card gs-border absolute right-0 z-20 mt-2 rounded-xl border p-5 shadow-lg"
                    >
                        <div class="flex gap-4">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-baseline gap-2">
                                    <label for="start-date" class="gs-text-sub text-xs font-bold tracking-[0.06em] uppercase">Start date</label>
                                    <button
                                        type="button"
                                        class="gs-accent-text cursor-pointer text-[11px] font-semibold"
                                        @click="range.startDate = props.firstSessionDate"
                                    >
                                        First day
                                    </button>
                                </div>
                                <input
                                    id="start-date"
                                    v-model="range.startDate"
                                    name="start-date"
                                    type="date"
                                    class="gs-input gs-text-input rounded-lg px-3 py-2 text-sm"
                                />
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-baseline gap-2">
                                    <label for="end-date" class="gs-text-sub text-xs font-bold tracking-[0.06em] uppercase">End date</label>
                                    <button
                                        type="button"
                                        class="gs-accent-text cursor-pointer text-[11px] font-semibold"
                                        @click="range.endDate = DateTime.today().toDateString()"
                                    >
                                        Today
                                    </button>
                                </div>
                                <input
                                    id="end-date"
                                    v-model="range.endDate"
                                    name="end-date"
                                    type="date"
                                    class="gs-input gs-text-input rounded-lg px-3 py-2 text-sm"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2" role="group" aria-label="Date range presets">
                            <button
                                type="button"
                                class="gs-text-strong cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold hover:underline"
                                @click="applyRange(lastWeekRange())"
                            >
                                Last week
                            </button>
                            <button
                                type="button"
                                class="gs-text-strong cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold hover:underline"
                                @click="applyRange(lastMonthRange())"
                            >
                                Last month
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gs-border flex flex-wrap items-center gap-3 border-t pt-4">
                <label class="gs-text-strong flex cursor-pointer items-center gap-2 text-sm font-semibold">
                    <input v-model="filter.shouldUseList" name="apply-list" type="checkbox" class="accent-vehikl-orange cursor-pointer" />
                    Apply list to filter
                </label>
                <button type="button" class="gs-btn-secondary cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold" @click="clearList">
                    Clear
                </button>

                <div class="ml-auto flex flex-wrap items-center justify-end gap-3">
                    <span v-if="shareResult === 'copied'" role="status" class="text-sm font-semibold text-green-600">Link copied to clipboard</span>
                    <span v-else-if="shareResult === 'failed'" role="status" class="text-sm font-semibold text-red-600">
                        Could not copy the link — copy it from the address bar instead
                    </span>
                    <button type="button" class="gs-btn-primary cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold" @click="copyShareableUrl">
                        Share URL
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm" :aria-busy="props.isLoading ? 'true' : 'false'">
                <thead>
                    <tr class="gs-border border-b">
                        <th
                            v-for="column in COLUMNS"
                            :key="column.field"
                            scope="col"
                            :aria-sort="ariaSortFor(column.field)"
                            :class="[
                                'gs-text-sub pb-2 text-xs font-bold tracking-[0.06em] whitespace-nowrap uppercase',
                                column.numeric ? 'text-right' : 'text-left',
                            ]"
                        >
                            <button type="button" class="cursor-pointer hover:underline" @click="sortBy(column.field)">
                                {{ column.label }}
                                <span aria-hidden="true">{{ sort.field === column.field ? (sort.direction === 'asc' ? '▲' : '▼') : '' }}</span>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="member in rows" :key="member.user_id" class="gs-border border-b last:border-0">
                        <td class="gs-text-strong py-2.5 pr-4 font-semibold whitespace-nowrap">{{ member.name }}</td>
                        <td class="gs-text-body py-2.5 text-right tabular-nums">{{ member.sessions_hosted_count }}</td>
                        <td class="gs-text-body py-2.5 text-right tabular-nums">{{ member.sessions_attended_count }}</td>
                        <td class="gs-text-body py-2.5 text-right tabular-nums">{{ member.sessions_watched_count }}</td>
                        <td class="gs-text-strong py-2.5 text-right font-semibold tabular-nums">{{ member.total_sessions_count }}</td>
                        <td class="py-2.5 text-right tabular-nums">
                            <span v-if="member.has_not_mobbed_with_count === 0" :title="`${member.name} has mobbed with everyone`">🎉</span>
                            <button
                                v-else
                                type="button"
                                class="gs-accent-text cursor-pointer font-semibold underline"
                                :aria-label="`See who ${member.name} has yet to mob with`"
                                @click="openMember = member"
                            >
                                {{ member.has_not_mobbed_with_count }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p v-if="props.isLoading" class="gs-text-muted py-6 text-center text-sm">Recalculating…</p>
            <p v-else-if="rows.length === 0" class="gs-text-muted py-6 text-center text-sm">No members match these filters.</p>
        </div>

        <div class="gs-border mt-4 flex flex-wrap items-center justify-between gap-3 border-t pt-4">
            <p class="gs-text-muted text-xs">{{ showingSummary }}</p>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="gs-btn-secondary cursor-pointer rounded-lg px-3 py-1.5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="page <= 1"
                    @click="page -= 1"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="gs-btn-secondary cursor-pointer rounded-lg px-3 py-1.5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="page >= pageCount"
                    @click="page += 1"
                >
                    Next
                </button>
            </div>
        </div>

        <div
            v-if="openMember"
            class="gs-overlay-bg fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="yet-to-mob-with-title"
            @click.self="openMember = null"
        >
            <div class="gs-card gs-border max-h-[80vh] w-full max-w-2xl overflow-auto rounded-2xl border p-6 shadow-2xl">
                <h3 id="yet-to-mob-with-title" class="gs-text-strong font-display mb-4 text-xl font-bold">
                    {{ openMember.name }} has yet to mob with
                </h3>
                <ul class="mb-6 grid grid-cols-2 gap-2 md:grid-cols-3">
                    <li
                        v-for="peer in openMember.has_not_mobbed_with"
                        :key="peer.id"
                        class="gs-secondary-bg gs-text-body rounded-lg px-3 py-2 text-sm"
                    >
                        {{ peer.name }}
                    </li>
                </ul>
                <button type="button" class="gs-btn-primary w-full cursor-pointer rounded-lg px-4 py-2.5 font-semibold" @click="openMember = null">
                    OK
                </button>
            </div>
        </div>
    </section>
</template>
