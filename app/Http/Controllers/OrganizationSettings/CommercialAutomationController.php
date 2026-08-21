<?php

namespace App\Http\Controllers\OrganizationSettings;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommercialAutomationController extends Controller
{
    public function edit(TenantContext $tenant): View
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->authorize('viewSettings', $organization);

        $defaults = config('commercial_automation.defaults', []);
        $stored = $organization->settings['commercial_automation'] ?? [];

        return view('organization-settings.commercial-automation', [
            'organization' => $organization,
            'settings' => array_merge($defaults, is_array($stored) ? $stored : []),
            'gateways' => config('commercial_automation.gateways', []),
        ]);
    }

    public function update(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'invoice_due_days_before' => ['required', 'integer', 'min:0', 'max:90'],
            'quotation_expiry_days_before' => ['required', 'integer', 'min:0', 'max:90'],
            'payment_gateway' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('commercial_automation.gateways', [])))],
        ]);

        $settings = $organization->settings ?? [];
        $settings['commercial_automation'] = [
            'invoice_due_reminders' => $request->boolean('invoice_due_reminders'),
            'invoice_due_days_before' => (int) $validated['invoice_due_days_before'],
            'invoice_overdue_reminders' => $request->boolean('invoice_overdue_reminders'),
            'payment_confirmation' => $request->boolean('payment_confirmation'),
            'payment_receipt' => $request->boolean('payment_receipt'),
            'quotation_expiry_reminders' => $request->boolean('quotation_expiry_reminders'),
            'quotation_expiry_days_before' => (int) $validated['quotation_expiry_days_before'],
            'sales_order_notifications' => $request->boolean('sales_order_notifications'),
            'payment_gateway' => $validated['payment_gateway'] ?? '',
        ];
        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('organization.settings.commercial-automation.edit')
            ->with('status', 'commercial-automation-updated');
    }
}
