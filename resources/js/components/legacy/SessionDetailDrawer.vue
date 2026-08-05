<script lang="ts" setup>
import { GrowthSession } from '@/classes/GrowthSession';
import CommentList from '@/components/legacy/CommentList.vue';
import LinkifiedText from '@/components/legacy/LinkifiedText.vue';
import LocationRenderer from '@/components/legacy/LocationRenderer.vue';
import ShareInviteLink from '@/components/legacy/ShareInviteLink.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { useCopyStatus } from '@/composables/useCopyStatus';
import { loginUrl } from '@/lib/loginUrl';
import { capacityLabel, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import { IUser } from '@/types';
import { ChevronRight, Forward, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

interface IProps {
    growthSession: GrowthSession;
    user?: IUser;
}

const props = defineProps<IProps>();
const emit = defineEmits(['close', 'edit-requested', 'delete-requested', 'refresh']);

const panel = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

/**
 * The caller keeps us mounted for the leave transition, so unmount is too late to stop
 * trapping focus: a `Tab` straight after `Escape` would be pulled back into the panel that
 * is already sliding away. Every exit goes through `close()`, which stands the trap down first.
 */
const isActive = ref(true);

function close(): void {
    isActive.value = false;
    emit('close');
}

/** Edit and delete close the drawer on the caller's side, so they stand the trap down too. */
function request(event: 'edit-requested' | 'delete-requested'): void {
    isActive.value = false;
    emit(event, props.growthSession);
}

function focusableElements(): HTMLElement[] {
    if (!panel.value) return [];
    return Array.from(
        panel.value.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'),
    ).filter((el) => el.offsetParent !== null || el === panel.value);
}

function onKeydown(event: KeyboardEvent) {
    if (!isActive.value) return;

    if (event.key === 'Escape') {
        close();
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

const { status: shareResult, copy } = useCopyStatus();

async function share() {
    await copy(props.growthSession.shareUrl);
}
</script>

<template>
    <div class="gs-overlay-bg fixed inset-0 z-30 flex justify-end" role="dialog" aria-modal="true" aria-labelledby="drawer-title" @click="close">
        <div
            ref="panel"
            tabindex="-1"
            class="gs-card gs-drawer-panel h-full w-full max-w-2xl overflow-y-auto p-7 shadow-2xl outline-none"
            @click.stop
        >
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <UserAvatar :name="growthSession.owner.name" :avatar="growthSession.owner.avatar" />
                    <span class="gs-text-sub text-sm font-semibold tracking-[0.03em]">{{ growthSession.owner.name }}</span>
                </div>
                <button
                    type="button"
                    class="gs-text-muted transition-smooth hover:text-gs-accent cursor-pointer text-xs font-semibold tracking-[0.04em]"
                    aria-label="Close"
                    @click="close"
                >
                    <X :size="16" aria-hidden="true" />
                </button>
            </div>

            <h2 id="drawer-title" class="gs-text-strong font-display mb-2.5 text-2xl leading-tight font-bold">{{ growthSession.title }}</h2>

            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="gs-text-sub text-xs font-semibold">{{ growthSession.startTime }}–{{ growthSession.endTime }}</span>
                    <span class="flex items-center gap-1.5 text-xs font-bold tracking-[0.05em]" :style="{ color: meta.color }">
                        <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: meta.color }"></span>{{ meta.label }}
                    </span>
                </div>
                <button
                    type="button"
                    class="share-button gs-btn-secondary flex flex-none cursor-pointer items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold"
                    @click="share"
                >
                    <Forward :size="16" aria-hidden="true" />
                    Share
                </button>
            </div>

            <div v-if="growthSession.tags.length" class="mb-4 flex flex-wrap gap-1.5">
                <span
                    v-for="tag in growthSession.tags"
                    :key="tag.id"
                    class="gs-text-sub gs-border rounded-full border px-2.5 py-1 text-xs font-semibold tracking-[0.05em]"
                    >{{ tag.name }}</span
                >
            </div>

            <p class="gs-text-body mb-5 text-sm leading-[1.6] whitespace-pre-wrap">
                <LinkifiedText :text="growthSession.topic" />
            </p>

            <div class="mb-6 flex flex-col gap-2.5">
                <a v-if="!user" :href="loginUrl()" class="login-to-join-link gs-btn-primary rounded-md py-3 text-center text-sm font-semibold">
                    Log in to join
                </a>
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
                    @click="request('edit-requested')"
                >
                    Edit
                </button>
                <button
                    v-if="growthSession.canEditOrDelete(user)"
                    type="button"
                    class="delete-button transition-smooth cursor-pointer rounded-md border border-red-500 py-3 text-sm font-semibold text-red-500 hover:bg-red-500 hover:text-white"
                    @click="request('delete-requested')"
                >
                    Delete
                </button>
                <span v-if="shareResult === 'copied'" role="status" class="text-center text-sm font-semibold text-green-600">
                    Link copied to clipboard
                </span>
                <span v-else-if="shareResult === 'failed'" role="status" class="gs-text-body text-center text-sm">
                    Could not copy the link — <span class="gs-accent-text font-medium break-all select-all">{{ growthSession.shareUrl }}</span>
                </span>
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
                <div v-if="growthSession.share_url">
                    <div class="gs-text-muted mb-1 text-xs font-bold tracking-[0.06em]">INVITE LINK</div>
                    <share-invite-link :share-url="growthSession.share_url" />
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
                                <UserAvatar :name="attendee.name" :avatar="attendee.avatar" />
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
                            <UserAvatar :name="w.name" :avatar="w.avatar" />
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

<style scoped>
/* Open and close animation. The caller mounts this component behind a <Transition name="gs-drawer">
   so closing plays in reverse instead of tearing the drawer out of the DOM; Vue puts the
   gs-drawer-* classes on our root element, which carries this block's scope id. */
.gs-drawer-enter-active,
.gs-drawer-leave-active {
    transition: opacity 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Same duration as the overlay fade, so Vue does not unmount us mid-slide. */
.gs-drawer-enter-active .gs-drawer-panel,
.gs-drawer-leave-active .gs-drawer-panel {
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.gs-drawer-enter-from,
.gs-drawer-leave-to {
    opacity: 0;
}

.gs-drawer-enter-from .gs-drawer-panel,
.gs-drawer-leave-to .gs-drawer-panel {
    transform: translateX(100%);
}

/* Let clicks reach the page again as soon as we start closing. */
.gs-drawer-leave-active {
    pointer-events: none;
}
</style>
