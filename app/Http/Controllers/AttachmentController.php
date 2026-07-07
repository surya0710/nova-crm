<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(protected AttachmentService $attachmentService) {}

    public function store(StoreAttachmentRequest $request): RedirectResponse
    {
        $model = $request->resolveAttachable();
        $this->authorize('update', $model);

        $this->attachmentService->store($model, $request->file('file'), $request->user());

        return back()->with('status', 'attachment-uploaded');
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        abort_unless(request()->user()->hasPermission('attachments.view', $attachment->organization), 403);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('attachments.delete', $attachment->organization), 403);

        $this->attachmentService->delete($attachment);

        return back()->with('status', 'attachment-deleted');
    }
}
