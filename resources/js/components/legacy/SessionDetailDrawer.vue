<script lang="ts" setup>
import { GrowthSession } from '@/classes/GrowthSession';
import CommentList from '@/components/legacy/CommentList.vue';
import LocationRenderer from '@/components/legacy/LocationRenderer.vue';
import { useInitials } from '@/composables/useInitials';
import { avatarColor, capacityLabel, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { IUser } from '@/types';
import { ChevronRight } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

interface IProps {
    growthSession: GrowthSession;
    user?: IUser;
}

const props = defineProps<IProps>();
const emit = defineEmits(['close', 'edit-requested', 'delete-requested', 'refresh']);

const panel = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

function focusableElements(): HTMLElement[] {
    if (!panel.value) return [];
    return Array.from(
        panel.value.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'),
    ).filter((el) => el.offsetParent !== null || el === panel.value);
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        emit('close');
        return;
    }

    if (event.key !== 'Tab') return;

    const focusable = focusableElements();
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

onMounted(() => {
    previouslyFocused = document.activeElement as HTMLElement | null;
    document.addEventListener('keydown', onKeydown);
    nextTick(() => (focusableElements()[0] ?? panel.value)?.focus());
});
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    previouslyFocused?.focus();
});

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
    <div class="gs-overlay-bg gs-fade-in fixed inset-0 z-30 flex justify-end" role="dialog" aria-modal="true" aria-labelledby="drawer-title" @click="emit('close')">
        <div ref="panel" tabindex="-1" class="gs-card gs-drawer-panel h-full max-w-2xl overflow-y-auto p-7 shadow-2xl outline-none" @click.stop>
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span
                        class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
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
                    <span class="gs-text-sub text-sm font-semibold tracking-[0.03em]">{{ growthSession.owner.name }}</span>
                </div>
                <button
                    type="button"
                    class="gs-text-muted transition-smooth hover:text-gs-accent cursor-pointer text-xs font-semibold tracking-[0.04em]"
                    @click="emit('close')"
                >
                    CLOSE ✕
                </button>
            </div>

            <h2 id="drawer-title" class="gs-text-strong font-display mb-2.5 text-2xl leading-tight font-bold">{{ growthSession.title }}</h2>

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
                    class="join-button gs-btn-primary cursor-pointer rounded-md py-3 text-sm font-semibold"
                    @click="join"
                >
                    Join
                </button>
                <button
                    v-show="growthSession.canWatch(user)"
                    type="button"
                    class="watch-button gs-btn-secondary cursor-pointer rounded-md py-3 text-sm font-semibold"
                    @click="watch"
                >
                    Spectate
                </button>
                <button
                    v-show="growthSession.canLeave(user)"
                    type="button"
                    class="leave-button transition-smooth cursor-pointer rounded-md border border-red-500 py-3 text-sm font-semibold text-red-500 hover:bg-red-500 hover:text-white"
                    @click="leave"
                >
                    Leave
                </button>
                <button
                    v-if="growthSession.canEditOrDelete(user)"
                    type="button"
                    class="update-button gs-btn-primary cursor-pointer rounded-md py-3 text-sm font-semibold"
                    @click="emit('edit-requested', growthSession)"
                >
                    Edit
                </button>
                <button
                    v-if="growthSession.canEditOrDelete(user)"
                    type="button"
                    class="delete-button transition-smooth cursor-pointer rounded-md border border-red-500 py-3 text-sm font-semibold text-red-500 hover:bg-red-500 hover:text-white"
                    @click="emit('delete-requested', growthSession)"
                >
                    Delete
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
                    <ul class="flex flex-col gap-1">
                        <li v-for="attendee in growthSession.attendees" :key="attendee.id">
                            <a
                                :href="attendee.githubURL"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group focus-visible:ring-gs-accent flex items-center gap-2.5 rounded-md px-2 py-1.5 transition-colors hover:bg-black/5 focus-visible:ring-2 focus-visible:outline-none dark:hover:bg-white/8"
                            >
                                <span
                                    class="flex h-8 w-8 flex-none items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                                    :style="{ backgroundColor: avatarColor(attendee.name) }"
                                >
                                    <img v-if="attendee.avatar" :src="attendee.avatar" :alt="attendee.name" class="h-full w-full object-cover" />
                                    <template v-else>{{ getInitials(attendee.name) }}</template>
                                </span>
                                <span
                                    class="gs-text-strong group-hover:text-gs-accent min-w-0 flex-1 text-sm font-semibold tracking-[0.02em] transition-colors"
                                    >{{ attendee.name }}</span
                                >
                                <ChevronRight
                                    aria-hidden="true"
                                    :size="17"
                                    :stroke-width="2"
                                    class="gs-text-muted group-hover:text-gs-accent flex-none transition-transform group-hover:translate-x-0.5"
                                />
                            </a>
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
