<script lang="ts" setup>
import { IGrowthSessionProposal, IUser } from '@/types';
import { computed } from 'vue';

interface Props {
    proposal: IGrowthSessionProposal;
    currentUser: IUser;
}

const props = defineProps<Props>();
const emit = defineEmits(['edit', 'delete', 'approve']);

const canEdit = computed(() => {
    return props.currentUser.id === props.proposal.creator.id || props.currentUser.is_vehikl_member;
});

const canApprove = computed(() => {
    return props.currentUser.is_vehikl_member && props.proposal.status === 'pending';
});

const statusColor = computed(() => {
    switch (props.proposal.status) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
});

function formatTimePreferences(): string {
    return props.proposal.time_preferences.map((pref) => `${pref.weekday}: ${pref.start_time} - ${pref.end_time}`).join(', ');
}
</script>

<template>
    <div class="proposal-card mb-4 rounded-lg bg-white p-6 shadow-md">
        <div class="mb-4 flex items-start justify-between">
            <div class="flex-1">
                <h3 class="mb-2 text-xl font-bold text-slate-800">{{ proposal.title }}</h3>
                <span :class="statusColor" class="inline-block rounded-full px-3 py-1 text-sm font-semibold uppercase">
                    {{ proposal.status }}
                </span>
            </div>
            <div class="flex gap-2">
                <button
                    v-if="canEdit"
                    @click="emit('edit', proposal)"
                    class="cursor-pointer rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
                >
                    Edit
                </button>
                <button
                    v-if="canApprove"
                    @click="emit('approve', proposal)"
                    class="cursor-pointer rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700"
                >
                    Approve
                </button>
                <button
                    v-if="canEdit"
                    @click="emit('delete', proposal)"
                    class="cursor-pointer rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
                >
                    Delete
                </button>
            </div>
        </div>

        <div class="mb-4">
            <h4 class="mb-1 text-sm font-bold text-slate-600 uppercase">Topic</h4>
            <p class="text-slate-700">{{ proposal.topic }}</p>
        </div>

        <div class="mb-4">
            <h4 class="mb-1 text-sm font-bold text-slate-600 uppercase">Proposed by</h4>
            <div class="flex items-center">
                <img :src="proposal.creator.avatar" :alt="proposal.creator.name" class="mr-2 h-8 w-8 rounded-full" />
                <span class="text-slate-700">{{ proposal.creator.name }}</span>
            </div>
        </div>

        <div class="mb-4">
            <h4 class="mb-1 text-sm font-bold text-slate-600 uppercase">Preferred Time Windows</h4>
            <p class="text-slate-700">{{ formatTimePreferences() }}</p>
        </div>

        <div v-if="proposal.tags.length > 0">
            <h4 class="mb-1 text-sm font-bold text-slate-600 uppercase">Tags</h4>
            <div class="flex flex-wrap gap-2">
                <span v-for="tag in proposal.tags" :key="tag.id" class="rounded-full bg-slate-200 px-3 py-1 text-sm text-slate-700">
                    {{ tag.name }}
                </span>
            </div>
        </div>
    </div>
</template>
