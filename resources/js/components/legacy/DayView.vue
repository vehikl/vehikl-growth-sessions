<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import TextSegments from '@/components/legacy/TextSegments.vue';
import { useInitials } from '@/composables/useInitials';
import { avatarColor, capacityLabel, ISessionAction, sessionActions, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { IUser } from '@/types';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface IProps {
    days: DateTime[];
    selectedIndex: number;
    sessions: GrowthSession[];
    currentLabel: string;
    user?: IUser;
}

/** A run of sessions that share a start/end window, shown under a single time label. */
interface ITimeSlot {
    key: string;
    timeRange: string;
    startsAtIso: string;
    sessions: GrowthSession[];
}

const props = defineProps<IProps>();
const emit = defineEmits(['select-day', 'open-detail', 'join', 'watch', 'leave', 'edit-requested', 'delete-requested', 'copy-requested', 'create']);
const statusClock = ref(Date.now());
let statusClockInterval: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    statusClockInterval = setInterval(() => (statusClock.value = Date.now()), 30_000);
});

onBeforeUnmount(() => clearInterval(statusClockInterval));

function currentStatus(session: GrowthSession) {
    void statusClock.value;
    return sessionStatus(session);
}

const { getInitials } = useInitials();

const isSelectedToday = computed<boolean>(() => !!props.days[props.selectedIndex]?.isToday());
const userIsInASession = computed<boolean>(
    () => !!props.user && props.sessions.some((s) => s.isOwner(props.user!) || s.isAttendeeOrWatcher(props.user!)),
);
const showCreateCta = computed<boolean>(() => !!props.user?.is_vehikl_member && isSelectedToday.value && !userIsInASession.value);

function tagline(session: GrowthSession): string {
    return session.tags.map((t) => (t.name.includes('&') ? t.name : t.name.charAt(0).toUpperCase() + t.name.slice(1).toLowerCase())).join(', ');
}

function actionsFor(session: GrowthSession): ISessionAction[] {
    return sessionActions(session, props.user);
}

function canAddToCalendar(session: GrowthSession): boolean {
    return currentStatus(session) !== 'finished';
}

function hasFooter(session: GrowthSession): boolean {
    return actionsFor(session).length > 0 || canAddToCalendar(session) || session.isOwner(props.user);
}

/** The day view gives its actions more room than a card does; the choosing is shared, the dressing is not. */
const ACTION_CLASSES: Record<string, string> = {
    join: 'gs-btn-primary max-w-48 px-4 md:px-20',
    'join-waitlist': 'gs-btn-primary max-w-48 px-4 text-center whitespace-nowrap md:w-48',
    watch: 'gs-btn-secondary px-5',
    leave: 'transition-smooth border border-red-500 px-5 text-red-500 hover:bg-red-500 hover:text-white',
    edit: 'gs-btn-primary max-w-48 px-4 md:px-20',
    delete: 'transition-smooth border border-red-500 px-5 text-red-500 hover:bg-red-500 hover:text-white',
};

/** Which event the parent expects for each action; the day view acts through the Board, not itself. */
const ACTION_EVENTS = {
    join: 'join',
    'join-waitlist': 'join',
    watch: 'watch',
    leave: 'leave',
    edit: 'edit-requested',
    delete: 'delete-requested',
} as const;

/**
 * Sessions arrive in start order, so neighbours sharing a window belong to the same slot.
 * Only consecutive sessions are merged — a stray later session with the same window keeps
 * its own label rather than being pulled out of the running order.
 */
const timeSlots = computed<ITimeSlot[]>(() =>
    props.sessions.reduce<ITimeSlot[]>((slots, session) => {
        const currentSlot = slots[slots.length - 1];

        if (currentSlot?.timeRange === session.timeRange) {
            currentSlot.sessions.push(session);

            return slots;
        }

        slots.push({
            key: `${session.id}-${session.timeRange}`,
            timeRange: session.timeRange,
            startsAtIso: session.startsAtIso,
            sessions: [session],
        });

        return slots;
    }, []),
);
</script>

<template>
    <div class="flex min-h-96 flex-1 flex-col md:flex-row">
        <div
            class="gs-col gs-border flex w-full flex-none items-center justify-center gap-1.5 border-b px-3 py-2 md:w-[clamp(80px,16vw,120px)] md:flex-col md:justify-start md:border-r md:border-b-0 md:px-0 md:py-4"
        >
            <button
                v-for="(day, i) in days"
                :key="day.toDateString()"
                type="button"
                class="transition-smooth flex flex-1 cursor-pointer flex-col items-center gap-0.5 rounded-[10px] py-2.5 md:w-[86%] md:flex-none"
                :class="i === selectedIndex ? 'gs-accent-bg text-white' : 'hover:bg-black/5 dark:hover:bg-white/5'"
                @click="emit('select-day', i)"
            >
                <span class="text-xs font-bold tracking-[0.04em] uppercase" :class="i === selectedIndex ? 'text-white' : 'gs-text-muted'">{{
                    day.format('ddd')
                }}</span>
                <span class="font-display text-lg font-bold" :class="i === selectedIndex ? 'text-white' : 'gs-text-sub'">{{ day.format('D') }}</span>
            </button>
        </div>

        <!-- Session timeline -->
        <div class="mt-2 min-w-0 flex-1 px-[clamp(12px,4vw,28px)] py-5 md:mt-0">
            <div class="mb-4 flex items-center gap-2.5">
                <span class="gs-text-strong font-display text-base font-bold tracking-[0.03em] uppercase">{{ currentLabel }}</span>
                <button
                    v-if="user && user.is_vehikl_member && !days[selectedIndex]?.isInAPastDate()"
                    type="button"
                    class="create-growth-session gs-btn-primary flex h-6 w-6 flex-none cursor-pointer items-center justify-center rounded-full text-base leading-none font-bold"
                    title="Add a session"
                    aria-label="Add a session"
                    @click="emit('create')"
                >
                    +
                </button>
            </div>

            <!-- Call to action when the logged-in member isn't in any session today -->
            <button
                v-if="showCreateCta"
                type="button"
                class="transition-smooth mb-5 flex w-full cursor-pointer items-center gap-4 rounded-xl border-2 border-dashed px-5 py-4 text-left hover:bg-black/5 dark:hover:bg-white/5"
                :style="{ borderColor: 'var(--color-vehikl-orange)' }"
                @click="emit('create')"
            >
                <span
                    class="gs-accent-bg flex h-10 w-10 flex-none items-center justify-center rounded-full text-xl leading-none font-bold text-white"
                >
                    +
                </span>
                <span class="min-w-0">
                    <span class="gs-text-strong block text-base font-semibold">You're not in a session today</span>
                    <span class="gs-text-body block text-sm">Start a Growth Session and invite others to join</span>
                </span>
            </button>

            <p v-if="!sessions.length && !showCreateCta" class="gs-text-muted py-10 text-center text-sm">
                No growth sessions scheduled for this day.
            </p>

            <div v-for="slot in timeSlots" :key="slot.key" class="gs-divider-color flex flex-wrap gap-x-3.5 gap-y-1.5 border-b py-3 last:border-b-0">
                <time
                    :datetime="slot.startsAtIso"
                    class="session-time gs-text-sub block w-full flex-none text-sm font-semibold uppercase md:sticky md:top-4 md:w-32 md:self-start md:pt-3.5"
                >
                    {{ slot.timeRange }}
                </time>

                <div class="flex min-w-55 flex-1 flex-col gap-2.5">
                    <div
                        v-for="session in slot.sessions"
                        :key="session.id"
                        class="gs-card gs-border transition-smooth relative flex flex-col gap-2.5 rounded-lg border p-3 px-4 hover:shadow-md"
                        :style="{ opacity: currentStatus(session) === 'finished' ? 0.55 : 1 }"
                    >
                        <button
                            type="button"
                            class="absolute inset-0 z-10 h-full w-full cursor-pointer rounded-lg"
                            :aria-label="`View details for ${session.title}`"
                            @click="emit('open-detail', session)"
                        ></button>
                        <span
                            class="capacity-readout gs-secondary-bg absolute top-3 right-4 inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold"
                            :class="session.hasReachedAttendeeLimit() ? 'gs-at-capacity' : 'gs-text-muted'"
                            :title="session.hasReachedAttendeeLimit() ? 'This session is full' : undefined"
                        >
                            <i class="fa fa-user" aria-hidden="true"></i>{{ capacityLabel(session) }}
                        </span>

                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                                :style="{ backgroundColor: avatarColor(session.ownerName) }"
                            >
                                <img
                                    v-if="session.owner?.avatar"
                                    :src="session.owner.avatar"
                                    :alt="session.owner.name"
                                    class="h-full w-full object-cover"
                                />
                                <template v-else>{{ getInitials(session.ownerName) }}</template>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 pr-20">
                                    <span class="gs-text-strong text-base font-semibold">{{ session.title }}</span>
                                    <span
                                        class="h-2 w-2 flex-none rounded-full"
                                        :style="{ backgroundColor: statusMeta(currentStatus(session)).color }"
                                        :title="statusMeta(currentStatus(session)).label"
                                    ></span>
                                    <span v-if="currentStatus(session) === 'live'" class="live-session-label gs-accent-text text-xs font-bold"
                                        >LIVE</span
                                    >
                                </div>
                                <div class="gs-accent-text mt-1 text-xs font-bold tracking-[0.04em] uppercase">
                                    {{ session.ownerName }}
                                </div>
                                <div v-if="tagline(session)" class="gs-text-sub mt-1 text-sm">{{ tagline(session) }}</div>
                                <div
                                    class="gs-text-body pointer-events-none relative z-20 mt-2 max-w-full text-sm leading-normal whitespace-pre-wrap xl:max-w-1/2"
                                >
                                    <TextSegments :segments="session.topic_segments" />
                                </div>
                                <div class="gs-text-muted pointer-events-none relative z-20 mt-2 flex items-center gap-1.5 text-sm font-medium">
                                    <i class="fa fa-compass flex-none" aria-hidden="true"></i>
                                    <TextSegments :segments="session.location_segments" />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="hasFooter(session)"
                            class="session-footer gs-divider-color relative z-20 flex flex-wrap items-center gap-2 border-t pt-2.5"
                            @click.stop
                        >
                            <button
                                v-for="action in actionsFor(session)"
                                :key="action.kind"
                                type="button"
                                :class="[action.hook, ACTION_CLASSES[action.kind]]"
                                class="cursor-pointer rounded-md py-2 text-sm font-semibold"
                                @click.stop="emit(ACTION_EVENTS[action.kind], session)"
                            >
                                {{ action.label }}
                            </button>

                            <div class="ml-auto flex items-center gap-0.5">
                                <a
                                    v-if="canAddToCalendar(session)"
                                    aria-label="add-to-calendar"
                                    :href="session.calendarUrl"
                                    target="_blank"
                                    class="gs-text-muted transition-smooth hover:text-gs-accent inline-flex h-9 w-9 items-center justify-center rounded-md leading-none"
                                    title="Add to calendar"
                                    @click.stop
                                >
                                    <i aria-hidden="true" class="fa fa-calendar text-sm"></i>
                                </a>
                                <button
                                    v-if="session.isOwner(user)"
                                    type="button"
                                    class="copy-button gs-text-muted transition-smooth hover:text-gs-accent inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-md leading-none"
                                    title="Duplicate"
                                    @click.stop="emit('copy-requested', session)"
                                >
                                    <i aria-hidden="true" class="fa fa-copy text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
