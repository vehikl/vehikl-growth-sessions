<script lang="ts" setup>
import MemberAvatar from '@/components/MemberAvatar.vue';
import type { IMobSquadMember } from '@/types';

defineProps<{ members: IMobSquadMember[] }>();

function mobsLabel(sessionsTogetherCount: number): string {
    return `${sessionsTogetherCount} ${sessionsTogetherCount === 1 ? 'mob' : 'mobs'}`;
}
</script>

<template>
    <ol v-if="members.length" class="flex flex-col gap-4">
        <li v-for="(member, index) in members" :key="member.id" data-testid="mob-squad-member" class="flex items-center gap-3">
            <span data-testid="mob-squad-rank" class="gs-text-muted w-3 flex-none text-sm font-semibold tabular-nums">{{ index + 1 }}</span>
            <MemberAvatar data-testid="mob-squad-avatar" :name="member.name" :avatar="member.avatar" />
            <span class="gs-text-strong min-w-0 flex-1 truncate text-sm font-semibold">{{ member.name }}</span>
            <span class="gs-text-muted flex-none text-sm tabular-nums">{{ mobsLabel(member.sessions_together_count) }}</span>
        </li>
    </ol>
</template>
