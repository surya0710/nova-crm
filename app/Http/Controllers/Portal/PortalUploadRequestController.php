<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientUploadRequest;
use App\Models\Organization;
use App\Services\DiscussionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalUploadRequestController extends Controller
{
    public function __construct(protected DiscussionService $discussions) {}

    public function fulfill(Request $request, Organization $organization, ClientUploadRequest $uploadRequest): RedirectResponse
    {
        abort_unless((int) $uploadRequest->organization_id === (int) $organization->id, 404);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $this->discussions->fulfillUploadRequest($uploadRequest, $request->user('client'), $data['file']);

        return back()->with('status', __('File uploaded.'));
    }
}
