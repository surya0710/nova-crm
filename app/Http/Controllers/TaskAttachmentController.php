<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\TaskAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    public function __construct(protected TaskAttachmentService $attachmentService) {}

    public function index(Task $task): View
    {
        $this->authorize('view', $task);

        return view('tasks.attachments.index', [
            'task' => $task,
            'attachments' => $task->attachments()->with('uploader')->latest()->get(),
        ]);
    }

    public function store(StoreTaskAttachmentRequest $request, Task $task): RedirectResponse
    {
        $this->attachmentService->upload($task, $request->file('file'), $request->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-attachment-uploaded');
    }

    public function download(Task $task, TaskAttachment $attachment): StreamedResponse
    {
        $this->assertBelongsToTask($task, $attachment);
        $this->authorize('view', $attachment);

        return $this->attachmentService->download($attachment);
    }

    public function destroy(Task $task, TaskAttachment $attachment, Request $request): RedirectResponse
    {
        $this->assertBelongsToTask($task, $attachment);
        $this->authorize('delete', $attachment);

        $this->attachmentService->delete($attachment, $request->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-attachment-deleted');
    }

    protected function assertBelongsToTask(Task $task, TaskAttachment $attachment): void
    {
        abort_unless((int) $attachment->task_id === (int) $task->id, 404);
    }
}
