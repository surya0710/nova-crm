<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentPdfService
{
    public function download(Payment $payment): Response|StreamedResponse
    {
        $payment->loadMissing(['customer', 'invoice', 'organization', 'recorder']);

        $pdf = Pdf::loadView('pdf.payment', [
            'payment' => $payment,
            'organization' => $payment->organization,
        ]);

        return $pdf->download($payment->number.'.pdf');
    }

    public function output(Payment $payment): string
    {
        $payment->loadMissing(['customer', 'invoice', 'organization', 'recorder']);

        return Pdf::loadView('pdf.payment', [
            'payment' => $payment,
            'organization' => $payment->organization,
        ])->output();
    }
}
