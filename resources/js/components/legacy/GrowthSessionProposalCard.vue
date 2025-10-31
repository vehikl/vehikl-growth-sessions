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
    return props.proposal.time_preferences
        .map((pref) => `${pref.weekday}: ${pref.start_time} - ${pref.end_time}`)
        .join(', ');
}
</script>

<template>
    <div class="proposal-card bg-white shadow-md rounded-lg p-6 mb-4">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-800 mb-2">{{ proposal.title }}</h3>
                <span :class="statusColor" class="inline-block px-3 py-1 rounded-full text-sm font-semibold uppercase">
                    {{ proposal.status }}
                </span>
            </div>
            <div class="flex gap-2">
                <button
                    v-if="canEdit"
                    @click="emit('edit', proposal)"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Edit
                </button>
                <button
                    v-if="canApprove"
                    @click="emit('approve', proposal)"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                    Approve
                </button>
                <button
                    v-if="canEdit"
                    @click="emit('delete', proposal)"
                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                >
                    Delete
                </button>
            </div>
        </div>

        <div class="mb-4">
            <h4 class="text-sm font-bold text-slate-600 uppercase mb-1">Topic</h4>
            <p class="text-slate-700">{{ proposal.topic }}</p>
        </div>

        <div class="mb-4">
            <h4 class="text-sm font-bold text-slate-600 uppercase mb-1">Proposed by</h4>
            <div class="flex items-center">
                <img :src="proposal.creator.avatar" :alt="proposal.creator.name" class="w-8 h-8 rounded-full mr-2" />
                <span class="text-slate-700">{{ proposal.creator.name }}</span>
            </div>
        </div>

        <div class="mb-4">
            <h4 class="text-sm font-bold text-slate-600 uppercase mb-1">Preferred Time Windows</h4>
            <p class="text-slate-700">{{ formatTimePreferences() }}</p>
        </div>

        <div v-if="proposal.tags.length > 0">
            <h4 class="text-sm font-bold text-slate-600 uppercase mb-1">Tags</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="tag in proposal.tags"
                    :key="tag.id"
                    class="bg-slate-200 text-slate-700 px-3 py-1 rounded-full text-sm"
                >
                    {{ tag.name }}
                </span>
            </div>
        </div>
    </div>
</template>
