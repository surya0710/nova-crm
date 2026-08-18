<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotationPdfService
{
    public function download(Quotation $quotation): Response|StreamedResponse
    {
        $quotation->loadMissing(['customer', 'items.product', 'organization', 'creator']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'organization' => $quotation->organization,
        ]);

        return $pdf->download($quotation->number.'.pdf');
    }

    public function output(Quotation $quotation): string
    {
        $quotation->loadMissing(['customer', 'items.product', 'organization', 'creator']);

        return Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'organization' => $quotation->organization,
        ])->output();
    }
}
