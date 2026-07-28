<script lang="ts" setup>
import { DateTime } from '@/classes/DateTime';
import { GrowthSession } from '@/classes/GrowthSession';
import { Nothingator } from '@/classes/Nothingator';
import GrowthSessionCard from '@/components/legacy/GrowthSessionCard.vue';
import { IUser } from '@/types';
import draggable from 'vuedraggable';

interface IProps {
    weekDates: DateTime[];
    sessionsByDate: Record<string, GrowthSession[]>;
    user?: IUser;
}

const props = defineProps<IProps>();
const emit = defineEmits(['create', 'edit-requested', 'copy-requested', 'open-detail', 'refresh', 'drag-change', 'drag-end']);

function sessionsFor(date: DateTime): GrowthSession[] {
    return props.sessionsByDate[date.toDateString()] ?? [];
}
</script>

<template>
    <div class="week-grid flex-1">
        <div
            v-for="date in weekDates"
            :key="date.toDateString()"
            :weekDay="date.weekDayString()"
            class="day gs-col gs-border flex flex-col border-r p-4"
        >
            <div class="mb-3.5 flex items-center justify-between gap-2">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span :id="date.weekDayString()" class="gs-text-strong font-display truncate text-base font-bold tracking-[0.04em] uppercase">
                        {{ date.weekDayString() }}
                    </span>
                    <button
                        v-if="user && user.is_vehikl_member && !date.isInAPastDate()"
                        type="button"
                        class="create-growth-session gs-btn-primary flex h-6 w-6 flex-none cursor-pointer items-center justify-center rounded-full text-base leading-none font-bold"
                        title="Add a session"
                        aria-label="Add a session"
                        @click="emit('create', date)"
                    >
                        +
                    </button>
                </div>
                <span class="gs-text-sub flex-none text-xs font-semibold tracking-[0.06em]">{{ date.format('MMM D').toUpperCase() }}</span>
            </div>

            <div v-show="sessionsFor(date).length === 0" class="gs-text-muted px-2 py-6 text-center text-sm">
                <p class="mb-1" v-text="`${Nothingator.random(date.toDateString())}...`" />
                <p v-show="user && date.isToday()" class="gs-text-muted text-xs">Why don't you create the first one?</p>
            </div>

            <draggable
                :model-value="sessionsFor(date)"
                :data-date="date.toDateString()"
                item-key="id"
                tag="ul"
                class="h-full w-full"
                group="growth-sessions"
                handle=".handle"
                @end="emit('drag-end', $event)"
                @change="emit('drag-change', $event)"
            >
                <template #item="{ element: growthSession }">
                    <li :key="growthSession.id">
                        <growth-session-card
                            :growth-session="growthSession"
                            :user="user"
                            @growth-session-updated="emit('refresh')"
                            @copy-requested="emit('copy-requested', $event)"
                            @edit-requested="emit('edit-requested', $event)"
                            @delete-requested="emit('refresh')"
                            @open-detail="emit('open-detail', $event)"
                        />
                    </li>
                </template>
            </draggable>
        </div>
    </div>
</template>

<style scoped>
.day {
    min-height: 14rem;
}

.week-grid {
    display: grid;
    grid-auto-flow: row;
    grid-template-columns: repeat(auto-fit, minmax(248px, 1fr));
    gap: 0;
}

@media (max-width: 767px) {
    .week-grid {
        grid-auto-rows: auto;
    }
}
</style>
