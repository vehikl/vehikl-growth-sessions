<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import TextSegments from '@/components/legacy/TextSegments.vue';
import { useInitials } from '@/composables/useInitials';
import { avatarColor, capacityLabel, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { IUser } from '@/types';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface IProps {
    days: DateTime[];
    selectedIndex: number;
    sessions: GrowthSession[];
    currentLabel: string;
    /** Ids of sessions the Board has determined should read as full. */
    fullSessionIds?: number[];
    user?: IUser;
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

function isFull(session: GrowthSession): boolean {
    return (props.fullSessionIds ?? []).includes(session.id);
}
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

            <div v-for="session in sessions" :key="session.id" class="gs-divider-color flex flex-wrap gap-x-3.5 gap-y-1.5 border-b py-3">
                <div class="gs-text-sub w-full flex-none text-sm font-semibold uppercase md:w-32 md:pt-3.5">
                    {{ session.startTime }} – {{ session.endTime }}
                </div>

                <div
                    class="gs-card gs-border transition-smooth relative flex min-w-55 flex-1 flex-col gap-2.5 rounded-lg border p-3 px-4 hover:shadow-md"
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
                            class="flex h-9 w-9 flex-none items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                            :style="{ backgroundColor: avatarColor(session.owner?.name ?? 'Unknown') }"
                        >
                            <img
                                v-if="session.owner?.avatar"
                                :src="session.owner.avatar"
                                :alt="session.owner.name"
                                class="h-full w-full object-cover"
                            />
                            <template v-else>{{ getInitials(session.owner?.name ?? 'Unknown') }}</template>
                        </span>
                        <!-- flex-1 so this column is as wide as the card allows; without it the column
                             shrinks to its content and percentage widths below resolve against that. -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 pr-20">
                                <span class="gs-text-strong text-base font-semibold">{{ session.title }}</span>
                                <span
                                    class="h-2 w-2 flex-none rounded-full"
                                    :style="{ backgroundColor: statusMeta(currentStatus(session)).color }"
                                    :title="statusMeta(currentStatus(session)).label"
                                ></span>
                                <span v-if="currentStatus(session) === 'live'" class="live-session-label gs-accent-text text-xs font-bold">LIVE</span>
                            </div>
                            <div class="gs-accent-text mt-1 text-xs font-bold tracking-[0.04em] uppercase">{{ session.owner?.name ?? 'Unknown' }}</div>
                            <div v-if="tagline(session)" class="gs-text-sub mt-1 text-sm">{{ tagline(session) }}</div>
                            <div
                                class="gs-text-body pointer-events-none relative z-20 mt-2 max-w-full text-sm leading-normal whitespace-pre-wrap xl:max-w-1/2"
                            >
                                <text-segments :segments="session.topic_segments" />
                            </div>
                            <div class="gs-text-muted pointer-events-none relative z-20 mt-2 flex items-center gap-1.5 text-sm font-medium">
                                <i class="fa fa-compass flex-none" aria-hidden="true"></i>
                                <text-segments :segments="session.location_segments" />
                            </div>
                        </div>
                    </div>

                    <div class="gs-divider-color relative z-20 flex flex-wrap items-center gap-2 border-t pt-2.5" @click.stop>
                        <button
                            v-show="session.canJoin(user)"
                            type="button"
                            class="join-button gs-btn-primary max-w-48 cursor-pointer rounded-md px-4 py-2 text-sm font-semibold md:px-20"
                            @click.stop="emit('join', session)"
                        >
                            Join
                        </button>
                        <span
                            v-if="isFull(session)"
                            class="full-indicator gs-at-capacity max-w-48 rounded-md border border-current px-4 py-2 text-center text-sm font-semibold md:px-20"
                            >Full</span
                        >
                        <button
                            v-show="session.canWatch(user)"
                            type="button"
                            class="watch-button gs-btn-secondary cursor-pointer rounded-md px-5 py-2 text-sm font-semibold"
                            @click.stop="emit('watch', session)"
                        >
                            Spectate
                        </button>
                        <button
                            v-show="session.canLeave(user)"
                            type="button"
                            class="leave-button transition-smooth cursor-pointer rounded-md border border-red-500 px-5 py-2 text-sm font-semibold text-red-500 hover:bg-red-500 hover:text-white"
                            @click.stop="emit('leave', session)"
                        >
                            Leave
                        </button>
                        <button
                            v-show="session.canEditOrDelete(user)"
                            type="button"
                            class="update-button gs-btn-primary max-w-48 cursor-pointer rounded-md px-4 py-2 text-sm font-semibold md:px-20"
                            @click.stop="emit('edit-requested', session)"
                        >
                            Edit
                        </button>
                        <button
                            v-show="session.canEditOrDelete(user)"
                            type="button"
                            class="delete-button transition-smooth cursor-pointer rounded-md border border-red-500 px-5 py-2 text-sm font-semibold text-red-500 hover:bg-red-500 hover:text-white"
                            @click.stop="emit('delete-requested', session)"
                        >
                            Delete
                        </button>

                        <a
                            aria-label="add-to-calendar"
                            :href="session.calendarUrl"
                            target="_blank"
                            class="gs-text-muted gs-border transition-smooth hover:text-gs-accent ml-auto inline-flex h-9 w-9 items-center justify-center rounded-md border"
                            title="Add to calendar"
                            @click.stop
                        >
                            <i class="fa fa-calendar text-sm" aria-hidden="true"></i>
                        </a>
                        <button
                            v-show="user && user.is_vehikl_member"
                            type="button"
                            class="copy-button gs-text-muted gs-border transition-smooth hover:text-gs-accent inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-md border"
                            title="Duplicate to another day"
                            @click.stop="emit('copy-requested', session)"
                        >
                            <i class="fa fa-copy text-sm" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
