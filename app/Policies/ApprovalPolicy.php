<?php

namespace App\Policies;

use App\Enums\StatusApproval;
use App\Models\Approval;
use App\Models\User;
use App\Services\ApprovalService;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['kepala_bagian', 'hrd', 'manager', 'admin']);
    }

    public function view(User $user, Approval $approval): bool
    {
        return $user->karyawan?->id === $approval->approver_id || $user->hasRole('admin');
    }

    /**
     * Hanya approver yang ditugaskan pada approval level tersebut yang boleh
     * bertindak, dan hanya jika ia memiliki permission sesuai level (Kepala
     * Bagian untuk level 1, HRD untuk level 2, Manager untuk level final).
     */
    public function act(User $user, Approval $approval): bool
    {
        if ($user->karyawan?->id !== $approval->approver_id || $approval->status !== StatusApproval::Pending) {
            return false;
        }

        return match ($approval->level) {
            ApprovalService::LEVEL_KEPALA_BAGIAN => $user->hasPermissionTo('cuti.approve-kepala-bagian'),
            ApprovalService::LEVEL_HRD => $user->hasPermissionTo('cuti.approve-hrd'),
            ApprovalService::LEVEL_MANAGER => $user->hasPermissionTo('cuti.approve-manager'),
            default => false,
        };
    }
}
