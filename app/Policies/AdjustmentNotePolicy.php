<?php

namespace App\Policies;

use App\Models\AdjustmentNote;
use App\Models\User;

class AdjustmentNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('adjustment_notes.view');
    }

    public function view(User $user, AdjustmentNote $adjustmentNote): bool
    {
        return $user->hasPermission('adjustment_notes.view', $adjustmentNote->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('adjustment_notes.create');
    }

    public function update(User $user, AdjustmentNote $adjustmentNote): bool
    {
        return $user->hasPermission('adjustment_notes.update', $adjustmentNote->organization)
            && $adjustmentNote->isEditable();
    }

    public function delete(User $user, AdjustmentNote $adjustmentNote): bool
    {
        return $user->hasPermission('adjustment_notes.delete', $adjustmentNote->organization)
            && $adjustmentNote->isDeletable();
    }

    public function issue(User $user, AdjustmentNote $adjustmentNote): bool
    {
        return $user->hasPermission('adjustment_notes.update', $adjustmentNote->organization)
            && $adjustmentNote->canIssue();
    }

    public function apply(User $user, AdjustmentNote $adjustmentNote): bool
    {
        return $user->hasPermission('adjustment_notes.update', $adjustmentNote->organization)
            && $adjustmentNote->canApply();
    }

    public function cancel(User $user, AdjustmentNote $adjustmentNote): bool
    {
        return $user->hasPermission('adjustment_notes.update', $adjustmentNote->organization)
            && $adjustmentNote->canCancel();
    }

    public function send(User $user, AdjustmentNote $adjustmentNote): bool
    {
        return $user->hasPermission('adjustment_notes.update', $adjustmentNote->organization);
    }
}
