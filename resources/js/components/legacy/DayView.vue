<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import LocationRenderer from '@/components/legacy/LocationRenderer.vue';
import { useInitials } from '@/composables/useInitials';
import { avatarColor, capacityLabel, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { IUser } from '@/types';

interface IProps {
    days: DateTime[];
    selectedIndex: number;
    sessions: GrowthSession[];
    currentLabel: string;
    user?: IUser;
}

defineProps<IProps>();
const emit = defineEmits(['select-day', 'open-detail', 'refresh', 'edit-requested', 'copy-requested']);

const { getInitials } = useInitials();

function tagline(session: GrowthSession): string {
    return session.tags.map((t) => (t.name.includes('&') ? t.name : t.name.charAt(0).toUpperCase() + t.name.slice(1).toLowerCase())).join(', ');
}

async function join(session: GrowthSession) {
    await session.join();
    emit('refresh');
}

async function watch(session: GrowthSession) {
    await session.watch();
    emit('refresh');
}

async function leave(session: GrowthSession) {
    await session.leave();
    emit('refresh');
}
</script>

<template>
    <div class="flex min-h-[24rem] flex-1">
        <!-- Day rail -->
        <div class="gs-col gs-border flex w-[clamp(64px,18vw,104px)] flex-none flex-col items-center gap-1.5 border-r py-4">
            <button
                v-for="(day, i) in days"
                :key="day.toDateString()"
                type="button"
                class="transition-smooth flex w-[86%] flex-col items-center gap-0.5 rounded-[10px] py-2.5"
                :class="i === selectedIndex ? 'gs-accent-bg text-white' : 'hover:bg-black/5 dark:hover:bg-white/5'"
                @click="emit('select-day', i)"
            >
                <span class="text-[9px] font-bold tracking-[0.04em] uppercase" :class="i === selectedIndex ? 'text-white' : 'gs-text-muted'">{{
                    day.format('ddd')
                }}</span>
                <span class="font-display text-base font-bold" :class="i === selectedIndex ? 'text-white' : 'gs-text-sub'">{{
                    day.format('D')
                }}</span>
            </button>
        </div>

        <!-- Session timeline -->
        <div class="min-w-0 flex-1 px-[clamp(12px,4vw,28px)] py-5">
            <div class="gs-text-strong font-display mb-4 text-sm font-bold tracking-[0.03em] uppercase">{{ currentLabel }}</div>

            <p v-if="!sessions.length" class="gs-text-muted py-10 text-center text-sm">No growth sessions scheduled for this day.</p>

            <div v-for="session in sessions" :key="session.id" class="gs-divider-color flex flex-wrap gap-x-3.5 gap-y-1.5 border-b py-3">
                <div class="gs-text-sub w-[78px] flex-none pt-3.5 text-[11px] font-semibold">{{ session.startTime }}–{{ session.endTime }}</div>

                <div
                    class="gs-card gs-border transition-smooth flex min-w-[220px] flex-1 cursor-pointer flex-col gap-2.5 rounded-lg border p-3 px-4 hover:shadow-md"
                    :style="{ opacity: sessionStatus(session) === 'finished' ? 0.55 : 1 }"
                    role="button"
                    tabindex="0"
                    @click="emit('open-detail', session)"
                    @keydown.enter="emit('open-detail', session)"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="flex min-w-0 items-start gap-3">
                            <span
                                class="flex h-[26px] w-[26px] flex-none items-center justify-center overflow-hidden rounded-full text-[10px] font-bold text-white"
                                :style="{ backgroundColor: avatarColor(session.owner.name) }"
                            >
                                <img
                                    v-if="session.owner.avatar"
                                    :src="session.owner.avatar"
                                    :alt="session.owner.name"
                                    class="h-full w-full object-cover"
                                />
                                <template v-else>{{ getInitials(session.owner.name) }}</template>
                            </span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="gs-text-strong text-[13px] font-semibold">{{ session.title }}</span>
                                    <span
                                        class="h-1.5 w-1.5 flex-none rounded-full"
                                        :style="{ backgroundColor: statusMeta(sessionStatus(session)).color }"
                                        :title="statusMeta(sessionStatus(session)).label"
                                    ></span>
                                </div>
                                <div class="gs-accent-text mt-0.5 text-[10px] font-bold tracking-[0.04em] uppercase">{{ session.owner.name }}</div>
                                <div v-if="tagline(session)" class="gs-text-sub mt-0.5 text-[11px]">{{ tagline(session) }}</div>
                                <div class="gs-text-body mt-1.5 max-w-[520px] text-[12px] leading-[1.4]">{{ session.topic }}</div>
                                <div class="gs-text-muted mt-1.5 flex items-center gap-1.5 text-[11px] font-medium break-all">
                                    <i class="fa fa-compass flex-none" aria-hidden="true"></i>
                                    <location-renderer :locationString="session.location" />
                                </div>
                            </div>
                        </div>
                        <span class="gs-secondary-bg gs-text-muted ml-3.5 flex-none rounded-full px-2.5 py-1 text-[10px] font-semibold">{{
                            capacityLabel(session)
                        }}</span>
                    </div>

                    <div class="gs-divider-color flex flex-wrap items-center gap-2 border-t pt-2.5" @click.stop>
                        <button
                            v-show="session.canJoin(user)"
                            type="button"
                            class="join-button gs-btn-primary rounded-md px-4 py-1.5 text-[11px] font-semibold"
                            @click.stop="join(session)"
                        >
                            Join
                        </button>
                        <button
                            v-show="session.canWatch(user)"
                            type="button"
                            class="watch-button gs-btn-secondary rounded-md px-4 py-1.5 text-[11px] font-semibold"
                            @click.stop="watch(session)"
                        >
                            Spectate
                        </button>
                        <button
                            v-show="session.canLeave(user)"
                            type="button"
                            class="leave-button transition-smooth rounded-md border border-red-500 px-4 py-1.5 text-[11px] font-semibold text-red-500 hover:bg-red-500 hover:text-white"
                            @click.stop="leave(session)"
                        >
                            Leave
                        </button>

                        <a
                            aria-label="add-to-calendar"
                            :href="session.calendarUrl"
                            target="_blank"
                            class="gs-text-muted gs-border transition-smooth hover:text-gs-accent ml-auto inline-flex h-[30px] w-[30px] items-center justify-center rounded-md border"
                            title="Add to calendar"
                            @click.stop
                        >
                            <i class="fa fa-calendar text-xs" aria-hidden="true"></i>
                        </a>
                        <button
                            v-show="user && user.is_vehikl_member"
                            type="button"
                            class="copy-button gs-text-muted gs-border transition-smooth hover:text-gs-accent inline-flex h-[30px] w-[30px] items-center justify-center rounded-md border"
                            title="Duplicate to another day"
                            @click.stop="emit('copy-requested', session)"
                        >
                            <i class="fa fa-copy text-xs" aria-hidden="true"></i>
                        </button>
                        <button
                            v-show="session.canEditOrDelete(user)"
                            type="button"
                            class="update-button gs-text-muted gs-border transition-smooth hover:text-gs-accent inline-flex h-[30px] w-[30px] items-center justify-center rounded-md border"
                            title="Edit session"
                            @click.stop="emit('edit-requested', session)"
                        >
                            <i class="fa fa-edit text-xs" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
