<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Models\OfferLetter;
use App\Models\Organization;
use Illuminate\View\View;

class CandidateOfferController extends Controller
{
    public function index(Organization $organization): View
    {
        $account = auth('candidate')->user();

        $offers = OfferLetter::query()
            ->with(['jobApplication.jobOpening', 'offerTemplate'])
            ->where('candidate_id', $account->candidate_id)
            ->latest()
            ->get();

        return view('careers.offers.index', [
            'organization' => $organization,
            'offers' => $offers,
        ]);
    }
}
