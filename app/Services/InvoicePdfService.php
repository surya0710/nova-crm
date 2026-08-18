<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicePdfService
{
    public function download(Invoice $invoice): Response|StreamedResponse
    {
        $invoice->loadMissing(['customer', 'items.product', 'organization', 'creator', 'payments']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'organization' => $invoice->organization,
        ]);

        return $pdf->download($invoice->number.'.pdf');
    }

    public function output(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'items.product', 'organization', 'creator', 'payments']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'organization' => $invoice->organization,
        ])->output();
    }
}
