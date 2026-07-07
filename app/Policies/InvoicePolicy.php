<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.view', $invoice->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.update', $invoice->organization)
            && ($invoice->isFullyEditable() || $invoice->isHeaderEditable());
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.delete', $invoice->organization)
            && $invoice->isDeletable();
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.update', $invoice->organization)
            && $invoice->canIssue();
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.update', $invoice->organization)
            && $invoice->canCancel();
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.update', $invoice->organization);
    }
}
