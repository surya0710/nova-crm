<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CrmRevenueController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'quotations.view',
                'invoices.view',
                'payments.view',
            ]),
            403
        );

        $canQuotes = $request->user()->hasPermission('quotations.view');
        $canInvoices = $request->user()->hasPermission('invoices.view');
        $canPayments = $request->user()->hasPermission('payments.view');

        $quotationCount = $canQuotes ? Quotation::query()->count() : 0;
        $invoiceCount = $canInvoices ? Invoice::query()->count() : 0;
        $paymentCount = $canPayments ? Payment::query()->count() : 0;

        $outstanding = null;
        if ($canInvoices && Schema::hasColumn('invoices', 'total') && Schema::hasColumn('invoices', 'amount_paid')) {
            $outstanding = (float) Invoice::query()
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as outstanding')
                ->value('outstanding');
        }

        $recentQuotations = $canQuotes
            ? Quotation::query()->with('customer')->latest()->limit(8)->get()
            : collect();

        $recentInvoices = $canInvoices
            ? Invoice::query()->with('customer')->latest()->limit(8)->get()
            : collect();

        $recentPayments = $canPayments
            ? Payment::query()->with(['customer', 'invoice'])->latest('payment_date')->latest()->limit(8)->get()
            : collect();

        return view('crm.revenue', [
            'organization' => $tenant->get(),
            'quotationCount' => $quotationCount,
            'invoiceCount' => $invoiceCount,
            'paymentCount' => $paymentCount,
            'outstanding' => $outstanding,
            'recentQuotations' => $recentQuotations,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'canQuotes' => $canQuotes,
            'canInvoices' => $canInvoices,
            'canPayments' => $canPayments,
        ]);
    }
}
