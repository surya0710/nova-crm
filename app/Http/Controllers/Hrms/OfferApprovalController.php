<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ProcessOfferApprovalRequest;
use App\Models\OfferApproval;
use App\Services\Recruitment\OfferApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferApprovalController extends Controller
{
    public function __construct(protected OfferApprovalService $service)
    {
        $this->authorizeResource(OfferApproval::class, 'offer_approval');
    }

    public function index(Request $request): View
    {
        $query = OfferApproval::query()
            ->with(['offerLetter.candidate', 'approver'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->boolean('mine')) {
            $query->where('approver_id', $request->user()->id);
        }

        return view('hrms.recruitment.offer-approvals.index', [
            'approvals' => $query->paginate(15)->withQueryString(),
            'statuses' => config('hrms.recruitment.offer_approval_statuses', []),
            'filterStatus' => $request->string('status')->toString(),
            'filterMine' => $request->boolean('mine'),
        ]);
    }

    public function show(OfferApproval $offerApproval): View
    {
        return view('hrms.recruitment.offer-approvals.show', [
            'approval' => $offerApproval->load(['offerLetter.candidate', 'offerLetter.jobApplication', 'approver']),
        ]);
    }

    public function approve(ProcessOfferApprovalRequest $request, OfferApproval $offerApproval): RedirectResponse
    {
        $this->service->approve($offerApproval, $request->user(), $request->validated('comments'));

        return redirect()->route('hrms.recruitment.offer-approvals.show', $offerApproval)
            ->with('status', 'recruitment-offer-approval-approved');
    }

    public function reject(ProcessOfferApprovalRequest $request, OfferApproval $offerApproval): RedirectResponse
    {
        $this->service->reject($offerApproval, $request->user(), $request->validated('comments'));

        return redirect()->route('hrms.recruitment.offer-approvals.show', $offerApproval)
            ->with('status', 'recruitment-offer-approval-rejected');
    }

    public function returnForRevision(ProcessOfferApprovalRequest $request, OfferApproval $offerApproval): RedirectResponse
    {
        $this->service->returnForRevision($offerApproval, $request->user(), $request->validated('comments'));

        return redirect()->route('hrms.recruitment.offer-approvals.show', $offerApproval)
            ->with('status', 'recruitment-offer-approval-returned');
    }
}
