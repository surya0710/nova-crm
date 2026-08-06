<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateOfferNegotiationRequest;
use App\Models\OfferLetter;
use App\Models\OfferNegotiation;
use App\Services\Recruitment\OfferNegotiationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OfferNegotiationController extends Controller
{
    public function __construct(protected OfferNegotiationService $service)
    {
        $this->authorizeResource(OfferNegotiation::class, 'offer_negotiation');
    }

    public function index(): View
    {
        return view('hrms.recruitment.negotiations.index', [
            'negotiations' => OfferNegotiation::query()
                ->with(['offerLetter.candidate'])
                ->latest()
                ->paginate(15),
            'offers' => OfferLetter::query()
                ->whereNotIn('status', ['accepted', 'rejected', 'expired', 'withdrawn'])
                ->with('candidate')
                ->latest()
                ->get(),
            'outcomes' => config('hrms.recruitment.negotiation_outcomes', []),
        ]);
    }

    public function show(OfferNegotiation $offerNegotiation): View
    {
        return view('hrms.recruitment.negotiations.show', [
            'negotiation' => $offerNegotiation->load(['offerLetter.candidate', 'offerLetter.jobApplication']),
        ]);
    }

    public function store(CreateOfferNegotiationRequest $request): RedirectResponse
    {
        $offer = OfferLetter::query()->findOrFail($request->validated('offer_letter_id'));
        $this->authorize('update', $offer);

        $negotiation = $this->service->recordNegotiation($offer, $request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.negotiations.show', $negotiation)
            ->with('status', 'recruitment-negotiation-created');
    }
}
