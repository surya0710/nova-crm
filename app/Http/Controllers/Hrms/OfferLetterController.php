<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\GenerateOfferLetterRequest;
use App\Http\Requests\Recruitment\SubmitOfferForApprovalRequest;
use App\Http\Requests\Recruitment\UpdateOfferLetterRequest;
use App\Models\JobApplication;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\User;
use App\Services\Recruitment\OfferLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferLetterController extends Controller
{
    public function __construct(protected OfferLetterService $service)
    {
        $this->authorizeResource(OfferLetter::class, 'offer_letter');
    }

    public function index(Request $request): View
    {
        $query = OfferLetter::query()
            ->with(['candidate', 'jobApplication.jobOpening', 'offerTemplate'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.recruitment.offers.index', [
            'offers' => $query->paginate(15)->withQueryString(),
            'applications' => JobApplication::query()->with('candidate')->where('status', 'active')->latest()->get(),
            'templates' => OfferTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => config('hrms.recruitment.offer_statuses', []),
            'filterStatus' => $request->string('status')->toString(),
        ]);
    }

    public function show(OfferLetter $offerLetter): View
    {
        return view('hrms.recruitment.offers.show', [
            'offer' => $offerLetter->load([
                'candidate', 'jobApplication.jobOpening', 'offerTemplate',
                'reportingManager', 'approvals.approver', 'negotiations',
            ]),
            'statuses' => config('hrms.recruitment.offer_statuses', []),
            'approvers' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function store(GenerateOfferLetterRequest $request): RedirectResponse
    {
        $offer = $this->service->generateOffer($request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.offers.show', $offer)
            ->with('status', 'recruitment-offer-created');
    }

    public function update(UpdateOfferLetterRequest $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->service->updateOffer($offerLetter, $request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.offers.show', $offerLetter)
            ->with('status', 'recruitment-offer-updated');
    }

    public function destroy(OfferLetter $offerLetter): RedirectResponse
    {
        $this->service->deleteOffer($offerLetter, request()->user());

        return redirect()->route('hrms.recruitment.offers.index')
            ->with('status', 'recruitment-offer-deleted');
    }

    public function submit(SubmitOfferForApprovalRequest $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        $this->service->submitForApproval(
            $offerLetter,
            $request->validated('approver_ids'),
            $request->user(),
        );

        return redirect()->route('hrms.recruitment.offers.show', $offerLetter)
            ->with('status', 'recruitment-offer-submitted');
    }

    public function send(Request $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        $this->service->sendOffer($offerLetter, $request->user());

        return redirect()->route('hrms.recruitment.offers.show', $offerLetter)
            ->with('status', 'recruitment-offer-sent');
    }

    public function accept(Request $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        $this->service->acceptOffer($offerLetter, $request->user());

        return redirect()->route('hrms.recruitment.offers.show', $offerLetter)
            ->with('status', 'recruitment-offer-accepted');
    }

    public function reject(Request $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        $this->service->rejectOffer($offerLetter, $request->user(), $request->string('reason')->toString() ?: null);

        return redirect()->route('hrms.recruitment.offers.show', $offerLetter)
            ->with('status', 'recruitment-offer-rejected');
    }

    public function withdraw(Request $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        $this->service->withdrawOffer($offerLetter, $request->user());

        return redirect()->route('hrms.recruitment.offers.show', $offerLetter)
            ->with('status', 'recruitment-offer-withdrawn');
    }
}
