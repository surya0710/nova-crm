<?php

namespace App\Services;

use App\Models\AdjustmentNote;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdjustmentNotePdfService
{
    public function download(AdjustmentNote $note): Response|StreamedResponse
    {
        $pdf = $this->make($note);

        return $pdf->download($note->number.'.pdf');
    }

    public function output(AdjustmentNote $note): string
    {
        return $this->make($note)->output();
    }

    protected function make(AdjustmentNote $note)
    {
        $note->loadMissing(['customer', 'items.product', 'organization', 'creator', 'invoice']);

        return Pdf::loadView('pdf.adjustment-note', [
            'note' => $note,
            'organization' => $note->organization,
        ]);
    }
}
