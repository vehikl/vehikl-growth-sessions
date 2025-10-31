<?php

namespace App\Policies;

use App\Models\GrowthSessionProposal;
use App\Models\User;

class GrowthSessionProposalPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, GrowthSessionProposal $proposal): bool
    {
        // Creator or any Vehikl member can view
        if (!$user) {
            return false;
        }
        return $user->id === $proposal->creator_id || $user->is_vehikl_member;
    }

    public function create(User $user): bool
    {
        // Any authenticated user can create a proposal
        return true;
    }

    public function update(User $user, GrowthSessionProposal $proposal): bool
    {
        // Creator or any Vehikl member can update
        return $user->id === $proposal->creator_id || $user->is_vehikl_member;
    }

    public function delete(User $user, GrowthSessionProposal $proposal): bool
    {
        // Creator or any Vehikl member can delete
        return $user->id === $proposal->creator_id || $user->is_vehikl_member;
    }

    public function approve(User $user, GrowthSessionProposal $proposal): bool
    {
        // Only Vehikl members can approve
        return $user->is_vehikl_member && $proposal->status === 'pending';
    }
}
