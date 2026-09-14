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

    /**
     * Level Kepala Bagian tetap milik satu approver spesifik (departemennya
     * sendiri). Level HRD & Manager adalah kolam bersama: approver_id di
     * situ cuma nilai awal/hint, jadi siapa pun dengan role tersebut boleh
     * melihatnya, bukan hanya yang namanya tercatat di approver_id.
     */
    public function view(User $user, Approval $approval): bool
    {
        if ($user->hasRole('admin') || $user->karyawan?->id === $approval->approver_id) {
            return true;
        }

        return match ($approval->level) {
            ApprovalService::LEVEL_HRD => $user->hasRole('hrd'),
            ApprovalService::LEVEL_MANAGER => $user->hasRole('manager'),
            default => false,
        };
    }

    /**
     * Kepala Bagian: hanya approver yang ditugaskan pada departemennya yang
     * boleh bertindak. HRD & Manager: siapa pun dengan permission level
     * tersebut boleh bertindak (kolam bersama, siapa cepat dia dapat) —
     * approver_id tidak lagi jadi pembatas, hanya dicatat ulang saat ada
     * yang benar-benar bertindak (lihat ApprovalService::approve/reject).
     */
    public function act(User $user, Approval $approval): bool
    {
        if ($approval->status !== StatusApproval::Pending) {
            return false;
        }

        return match ($approval->level) {
            ApprovalService::LEVEL_KEPALA_BAGIAN => $user->karyawan?->id === $approval->approver_id
                && $user->hasPermissionTo('cuti.approve-kepala-bagian'),
            ApprovalService::LEVEL_HRD => $user->hasPermissionTo('cuti.approve-hrd'),
            ApprovalService::LEVEL_MANAGER => $user->hasPermissionTo('cuti.approve-manager'),
            default => false,
        };
    }
}
