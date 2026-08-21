<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesOrderPdfService
{
    public function download(SalesOrder $salesOrder): Response|StreamedResponse
    {
        $salesOrder->loadMissing(['customer', 'items.product', 'organization', 'creator', 'quotation']);

        $pdf = Pdf::loadView('pdf.sales-order', [
            'salesOrder' => $salesOrder,
            'organization' => $salesOrder->organization,
        ]);

        return $pdf->download($salesOrder->number.'.pdf');
    }

    public function output(SalesOrder $salesOrder): string
    {
        $salesOrder->loadMissing(['customer', 'items.product', 'organization', 'creator', 'quotation']);

        return Pdf::loadView('pdf.sales-order', [
            'salesOrder' => $salesOrder,
            'organization' => $salesOrder->organization,
        ])->output();
    }
}
