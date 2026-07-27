<script lang="ts" setup>
import { GrowthSession } from '@/classes/GrowthSession';
import LocationRenderer from '@/components/legacy/LocationRenderer.vue';
import { useInitials } from '@/composables/useInitials';
import { avatarColor, sessionStatus, statusMeta } from '@/lib/sessionDisplay';
import IconDraggable from '@/svgs/IconDraggable.vue';
import { IUser } from '@/types';
import { computed } from 'vue';

interface IProps {
    growthSession: GrowthSession;
    user?: IUser;
}

const props = defineProps<IProps>();
const emit = defineEmits(['growth-session-updated', 'delete-requested', 'edit-requested', 'copy-requested', 'open-detail']);

const { getInitials } = useInitials();

const isDraggable = computed<boolean>(() => !!props.user && props.growthSession.canEditOrDelete(props.user));
const status = computed(() => sessionStatus(props.growthSession));
const statusColor = computed(() => statusMeta(status.value).color);
const initials = computed(() => getInitials(props.growthSession.owner.name));
const ownerColor = computed(() => avatarColor(props.growthSession.owner.name));
const cardOpacity = computed(() => (status.value === 'finished' ? 0.55 : 1));

const canSeeLocation = computed<boolean>(
    () => !!props.user && (props.growthSession.isOwner(props.user) || props.growthSession.isAttendeeOrWatcher(props.user)),
);

async function joinGrowthSession() {
    await props.growthSession.join();
    emit('growth-session-updated');
}

async function watchGrowthSession() {
    await props.growthSession.watch();
    emit('growth-session-updated');
}

async function leaveGrowthSession() {
    await props.growthSession.leave();
    emit('growth-session-updated');
}

async function onDeleteClicked() {
    if (confirm('Are you sure you want to delete?')) {
        try {
            await props.growthSession.delete();
        } catch (error: any) {
            if (error.response?.status !== 404) {
                console.error('Failed to delete growth session:', error);
            }
        } finally {
            emit('delete-requested', props.growthSession);
        }
    }
}
</script>

<template>
    <div
        class="group gs-card gs-border transition-smooth mb-3 cursor-pointer rounded-lg border p-4 hover:shadow-md"
        :style="{ opacity: cardOpacity }"
        role="button"
        tabindex="0"
        @click="emit('open-detail', growthSession)"
        @keydown.enter="emit('open-detail', growthSession)"
    >
        <div class="mb-3 flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2.5">
                <span
                    class="flex h-7 w-7 flex-none items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white"
                    :style="{ backgroundColor: ownerColor }"
                >
                    <img
                        v-if="growthSession.owner.avatar"
                        :src="growthSession.owner.avatar"
                        :alt="growthSession.owner.name"
                        class="h-full w-full object-cover"
                    />
                    <template v-else>{{ initials }}</template>
                </span>
                <span class="gs-text-sub min-w-0 truncate text-sm font-medium" v-text="growthSession.owner.name" />
            </div>
            <span class="h-2 w-2 flex-none rounded-full" :style="{ backgroundColor: statusColor }" :title="statusMeta(status).label"></span>
        </div>

        <h3 class="gs-text-strong mb-3 text-lg leading-snug font-semibold wrap-break-word" v-text="growthSession.title" />

        <div class="gs-text-sub mb-2 flex items-center justify-between text-sm font-medium">
            <span>{{ growthSession.startTime }} – {{ growthSession.endTime }}</span>
            <span class="attendees-count inline-flex items-center gap-1">
                <i class="fa fa-user text-xs" aria-hidden="true"></i>
                {{ growthSession.attendees.length
                }}<span v-if="growthSession.attendee_limit" class="attendee-limit">/{{ growthSession.attendee_limit }}</span>
            </span>
        </div>

        <div class="gs-text-muted mb-3 truncate text-sm">
            <location-renderer v-if="canSeeLocation" :locationString="growthSession.location" />
            <template v-else>&lt; Join to see location &gt;</template>
        </div>

        <div class="flex gap-1.5 empty:hidden" @click.stop>
            <button
                v-show="growthSession.canJoin(user)"
                type="button"
                class="join-button gs-btn-primary flex-3 rounded-sm py-1.5 px-4 text-sm font-medium"
                @click.stop="joinGrowthSession"
            >
                Join
            </button>
            <button
                v-show="growthSession.canWatch(user)"
                type="button"
                class="watch-button gs-btn-secondary flex-1 rounded-sm py-1.5 px-4 text-sm font-medium"
                @click.stop="watchGrowthSession"
            >
                Spectate
            </button>
            <button
                v-show="growthSession.canLeave(user)"
                type="button"
                class="leave-button transition-smooth flex-1 rounded-sm border border-red-500 py-1.5 px-4 text-sm font-medium text-red-500 hover:bg-red-500 hover:text-white"
                @click.stop="leaveGrowthSession"
            >
                Leave
            </button>
        </div>

        <!-- Owner / utility actions: collapsed until hover so the card stays clean like the design -->
        <div class="max-h-0 overflow-hidden opacity-0 transition-all duration-200 group-hover:max-h-12 group-hover:opacity-100" @click.stop>
            <div class="flex items-center gap-0.5 pt-2.5">
                <a
                    aria-label="add-to-calendar"
                    :href="growthSession.calendarUrl"
                    target="_blank"
                    class="gs-text-muted transition-smooth hover:text-gs-accent inline-flex h-9 w-9 items-center justify-center rounded-md leading-none"
                    title="Add to calendar"
                    @click.stop
                >
                    <i aria-hidden="true" class="fa fa-calendar text-sm"></i>
                </a>
                <button
                    v-show="growthSession.canEditOrDelete(user)"
                    type="button"
                    class="update-button gs-text-muted transition-smooth hover:text-gs-accent inline-flex h-9 w-9 items-center justify-center rounded-md leading-none"
                    title="Edit"
                    @click.stop="emit('edit-requested', growthSession)"
                >
                    <i aria-hidden="true" class="fa fa-edit text-sm"></i>
                </button>
                <button
                    v-show="user && user.is_vehikl_member"
                    type="button"
                    class="copy-button gs-text-muted transition-smooth hover:text-gs-accent inline-flex h-9 w-9 items-center justify-center rounded-md leading-none"
                    title="Duplicate"
                    @click.stop="emit('copy-requested', growthSession)"
                >
                    <i aria-hidden="true" class="fa fa-copy text-sm"></i>
                </button>
                <button
                    v-show="growthSession.canEditOrDelete(user)"
                    type="button"
                    class="delete-button gs-text-muted transition-smooth inline-flex h-9 w-9 items-center justify-center rounded-md leading-none hover:text-red-500"
                    title="Delete"
                    @click.stop="onDeleteClicked"
                >
                    <i class="fa fa-trash text-sm" aria-hidden="true"></i>
                </button>
                <div
                    v-if="isDraggable"
                    class="handle transition-smooth hover:text-gs-accent ml-auto inline-flex h-9 w-9 cursor-move items-center justify-center rounded-md"
                    @click.stop
                >
                    <icon-draggable class="gs-text-muted h-4" />
                </div>
            </div>
        </div>
    </div>
</template>
