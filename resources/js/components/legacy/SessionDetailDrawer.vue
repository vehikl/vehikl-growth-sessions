<script lang="ts" setup>
import { GrowthSession } from '@/classes/GrowthSession';
import CommentList from '@/components/legacy/CommentList.vue';
import LocationRenderer from '@/components/legacy/LocationRenderer.vue';
import { useInitials } from '@/composables/useInitials';
import { avatarColor, capacityLabel, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { IUser } from '@/types';
import { computed } from 'vue';

interface IProps {
    growthSession: GrowthSession;
    user?: IUser;
}

const props = defineProps<IProps>();
const emit = defineEmits(['close', 'edit-requested', 'refresh']);

const { getInitials } = useInitials();

const status = computed(() => sessionStatus(props.growthSession));
const meta = computed(() => statusMeta(status.value));
const mobtimeUrl = computed(() => `https://mobtime.vehikl.com/vgs-${props.growthSession.id}`);

async function join() {
    await props.growthSession.join();
    emit('refresh');
}

async function watch() {
    await props.growthSession.watch();
    emit('refresh');
}

async function leave() {
    await props.growthSession.leave();
    emit('refresh');
}
</script>

<template>
    <div class="gs-overlay-bg gs-fade-in fixed inset-0 z-30 flex justify-end" @click="emit('close')">
        <div class="gs-card gs-drawer-panel h-full w-[420px] max-w-[92vw] overflow-y-auto p-7 shadow-2xl" @click.stop>
            <div class="mb-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                        :style="{ backgroundColor: avatarColor(growthSession.owner.name) }"
                    >
                        <img
                            v-if="growthSession.owner.avatar"
                            :src="growthSession.owner.avatar"
                            :alt="growthSession.owner.name"
                            class="h-full w-full object-cover"
                        />
                        <template v-else>{{ getInitials(growthSession.owner.name) }}</template>
                    </span>
                    <span class="gs-text-sub text-xs font-semibold tracking-[0.03em]">{{ growthSession.owner.name }}</span>
                </div>
                <button
                    type="button"
                    class="gs-text-muted transition-smooth hover:text-gs-accent text-xs font-semibold tracking-[0.04em]"
                    @click="emit('close')"
                >
                    CLOSE ✕
                </button>
            </div>

            <h2 class="gs-text-strong font-display mb-2.5 text-2xl leading-tight font-bold">{{ growthSession.title }}</h2>

            <div class="mb-3 flex items-center gap-2.5">
                <span class="gs-text-sub text-xs font-semibold">{{ growthSession.startTime }}–{{ growthSession.endTime }}</span>
                <span class="flex items-center gap-1.5 text-xs font-bold tracking-[0.05em]" :style="{ color: meta.color }">
                    <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: meta.color }"></span>{{ meta.label }}
                </span>
            </div>

            <div v-if="growthSession.tags.length" class="mb-4 flex flex-wrap gap-1.5">
                <span
                    v-for="tag in growthSession.tags"
                    :key="tag.id"
                    class="gs-text-sub gs-border rounded-full border px-2.5 py-1 text-xs font-semibold tracking-[0.05em]"
                    >{{ tag.name }}</span
                >
            </div>

            <p class="gs-text-body mb-5 text-sm leading-[1.6] whitespace-pre-wrap">{{ growthSession.topic }}</p>

            <div class="mb-6 flex flex-col gap-2.5">
                <button
                    v-show="growthSession.canJoin(user)"
                    type="button"
                    class="join-button gs-btn-primary rounded-md py-3 text-sm font-semibold"
                    @click="join"
                >
                    Join
                </button>
                <button
                    v-show="growthSession.canWatch(user)"
                    type="button"
                    class="watch-button gs-btn-secondary rounded-md py-3 text-sm font-semibold"
                    @click="watch"
                >
                    Spectate
                </button>
                <button
                    v-show="growthSession.canLeave(user)"
                    type="button"
                    class="leave-button transition-smooth rounded-md border border-red-500 py-3 text-sm font-semibold text-red-500 hover:bg-red-500 hover:text-white"
                    @click="leave"
                >
                    Leave
                </button>
                <button
                    v-if="growthSession.canEditOrDelete(user)"
                    type="button"
                    class="update-button gs-btn-secondary rounded-md py-3 text-sm font-semibold"
                    @click="emit('edit-requested', growthSession)"
                >
                    Edit session
                </button>
            </div>

            <div class="gs-border flex flex-col gap-3.5 border-t pt-4">
                <div>
                    <div class="gs-text-muted mb-1 text-xs font-bold tracking-[0.06em]">LOCATION</div>
                    <div class="gs-text-strong text-sm font-medium"><location-renderer :locationString="growthSession.location" /></div>
                </div>
                <div v-if="growthSession.anydesk">
                    <div class="gs-text-muted mb-1 text-xs font-bold tracking-[0.06em]">ANYDESK</div>
                    <div class="gs-text-strong text-sm font-medium">{{ growthSession.anydesk.name }}: {{ growthSession.anydesk.remote_desk_id }}</div>
                </div>
                <div>
                    <div class="gs-text-muted mb-1 text-xs font-bold tracking-[0.06em]">MOBTIME</div>
                    <a :href="mobtimeUrl" target="_blank" class="gs-accent-text text-sm font-medium break-all">{{ mobtimeUrl }}</a>
                </div>
                <div>
                    <div class="gs-text-muted mb-2.5 text-xs font-bold tracking-[0.06em]">ATTENDEES ({{ capacityLabel(growthSession) }})</div>
                    <ul class="flex flex-col gap-2.5">
                        <li v-for="attendee in growthSession.attendees" :key="attendee.id" class="flex items-center gap-2.5">
                            <span
                                class="flex h-8 w-8 flex-none items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                                :style="{ backgroundColor: avatarColor(attendee.name) }"
                            >
                                <img v-if="attendee.avatar" :src="attendee.avatar" :alt="attendee.name" class="h-full w-full object-cover" />
                                <template v-else>{{ getInitials(attendee.name) }}</template>
                            </span>
                            <span class="gs-text-strong text-sm font-semibold tracking-[0.02em] uppercase">{{ attendee.name }}</span>
                        </li>
                    </ul>
                </div>
                <div v-if="growthSession.watchers.length">
                    <div class="gs-text-muted mb-2.5 text-xs font-bold tracking-[0.06em]">WATCHERS ({{ growthSession.watchers.length }})</div>
                    <ul class="flex flex-col gap-2.5">
                        <li v-for="w in growthSession.watchers" :key="w.id" class="flex items-center gap-2.5">
                            <span
                                class="flex h-8 w-8 flex-none items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                                :style="{ backgroundColor: avatarColor(w.name) }"
                            >
                                <img v-if="w.avatar" :src="w.avatar" :alt="w.name" class="h-full w-full object-cover" />
                                <template v-else>{{ getInitials(w.name) }}</template>
                            </span>
                            <span class="gs-text-strong text-sm font-semibold tracking-[0.02em] uppercase">{{ w.name }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="gs-border mt-5 border-t pt-4">
                <comment-list :growth-session="growthSession" :user="user" />
            </div>
        </div>
    </div>
</template>
