<script lang="ts" setup>
import { useInitials } from '@/composables/useInitials';
import { avatarColor } from '@/lib/sessionDisplay';
import type { IMobSquadMember } from '@/types';

defineProps<{ members: IMobSquadMember[] }>();

const { getInitials } = useInitials();

function mobsLabel(sessionsTogetherCount: number): string {
    return `${sessionsTogetherCount} ${sessionsTogetherCount === 1 ? 'mob' : 'mobs'}`;
}
</script>

<template>
    <ol v-if="members.length" class="flex flex-col gap-4">
        <li v-for="(member, index) in members" :key="member.id" data-testid="mob-squad-member" class="flex items-center gap-3">
            <span data-testid="mob-squad-rank" class="gs-text-muted w-3 flex-none text-sm font-semibold tabular-nums">{{ index + 1 }}</span>
            <span
                data-testid="mob-squad-avatar"
                :style="{ backgroundColor: avatarColor(member.name) }"
                class="flex h-8 w-8 flex-none items-center justify-center overflow-hidden rounded-full text-[11px] font-bold text-white"
            >
                <img v-if="member.avatar" :src="member.avatar" :alt="member.name" class="h-full w-full object-cover" />
                <template v-else>{{ getInitials(member.name) }}</template>
            </span>
            <span class="gs-text-strong min-w-0 flex-1 truncate text-sm font-semibold">{{ member.name }}</span>
            <span class="gs-text-muted flex-none text-sm tabular-nums">{{ mobsLabel(member.sessions_together_count) }}</span>
        </li>
    </ol>

    <slot v-else name="empty" />
</template>
