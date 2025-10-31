<script lang="ts" setup>
import { IGrowthSessionProposal, IUser } from '@/types';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import GrowthSessionProposalCard from '@/components/legacy/GrowthSessionProposalCard.vue';
import GrowthSessionProposalForm from '@/components/legacy/GrowthSessionProposalForm.vue';
import ApproveProposalForm from '@/components/legacy/ApproveProposalForm.vue';
import { GrowthSessionProposalApi } from '@/services/GrowthSessionProposalApi';

interface Props {
    proposals: IGrowthSessionProposal[];
    userJson: IUser;
}

const props = defineProps<Props>();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showApproveModal = ref(false);
const selectedProposal = ref<IGrowthSessionProposal | null>(null);

const pendingProposals = computed(() => props.proposals.filter((p) => p.status === 'pending'));
const approvedProposals = computed(() => props.proposals.filter((p) => p.status === 'approved'));

function openCreateModal() {
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(proposal: IGrowthSessionProposal) {
    selectedProposal.value = proposal;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    selectedProposal.value = null;
}

function openApproveModal(proposal: IGrowthSessionProposal) {
    selectedProposal.value = proposal;
    showApproveModal.value = true;
}

function closeApproveModal() {
    showApproveModal.value = false;
    selectedProposal.value = null;
}

async function handleDelete(proposal: IGrowthSessionProposal) {
    if (confirm('Are you sure you want to delete this proposal?')) {
        try {
            await GrowthSessionProposalApi.delete(proposal.id);
            router.reload();
        } catch (e) {
            alert('Failed to delete proposal');
        }
    }
}

function handleSubmitted() {
    closeCreateModal();
    closeEditModal();
    router.reload();
}

function handleApproved() {
    closeApproveModal();
    router.reload();
}
</script>

<template>
    <div class="proposals-page max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800">Growth Session Proposals</h1>
            <button @click="openCreateModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create Proposal
            </button>
        </div>

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-700 mb-4" v-if="$page.props.auth.user.is_vehikl_member">Pending Proposals ({{ pendingProposals.length }})</h2>
            <h2 class="text-2xl font-bold text-slate-700 mb-4" v-else>Need help with your codebase?</h2>
            <div v-if="pendingProposals.length === 0" class="text-slate-600 italic">
                <template v-if="$page.props.auth.user.is_vehikl_member">
                    No pending proposals.
                </template>
                <template v-else>
                    <p>Our team specializes in Laravel, Vue, React, and other modern web technologies.</p>
                    <p>Tell us what you're working on and what you’d like to achieve — we’ll jump in and collaborate with you through a mob programming session.</p>
                </template>
            </div>
            <GrowthSessionProposalCard
                v-for="proposal in pendingProposals"
                :key="proposal.id"
                :proposal="proposal"
                :current-user="userJson"
                @edit="openEditModal"
                @delete="handleDelete"
                @approve="openApproveModal"
            />
        </div>

        <div v-if="$page.props.auth.user.is_vehikl_member">
            <h2 class="text-2xl font-bold text-slate-700 mb-4">Approved Proposals ({{ approvedProposals.length }})</h2>
            <div v-if="approvedProposals.length === 0" class="text-slate-600 italic">
                No approved proposals yet.
            </div>
            <GrowthSessionProposalCard
                v-for="proposal in approvedProposals"
                :key="proposal.id"
                :proposal="proposal"
                :current-user="userJson"
                @edit="openEditModal"
                @delete="handleDelete"
                @approve="openApproveModal"
            />
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeCreateModal">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 relative">
                <button @click="closeCreateModal" class="absolute top-4 right-4 text-slate-600 hover:text-slate-900 text-2xl font-bold">
                    &times;
                </button>
                <GrowthSessionProposalForm :user="userJson" @submitted="handleSubmitted" />
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal && selectedProposal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeEditModal">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 relative">
                <button @click="closeEditModal" class="absolute top-4 right-4 text-slate-600 hover:text-slate-900 text-2xl font-bold">
                    &times;
                </button>
                <GrowthSessionProposalForm :user="userJson" :proposal="selectedProposal" @submitted="handleSubmitted" />
            </div>
        </div>

        <!-- Approve Modal -->
        <div v-if="showApproveModal && selectedProposal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeApproveModal">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 relative">
                <button @click="closeApproveModal" class="absolute top-4 right-4 text-slate-600 hover:text-slate-900 text-2xl font-bold">
                    &times;
                </button>
                <ApproveProposalForm :proposal="selectedProposal" @submitted="handleApproved" />
            </div>
        </div>
    </div>
</template>
