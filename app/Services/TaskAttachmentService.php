<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function upload(Task $task, UploadedFile $file, User $actor): TaskAttachment
    {
        if (! app(TaskAuthorizationService::class)->attachmentsEnabled()) {
            throw ValidationException::withMessages([
                'file' => __('Task attachments are currently disabled.'),
            ]);
        }

        if ($task->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }

        $directory = 'task-attachments/'.$task->organization_id;
        $path = $file->store($directory, 'public');

        return DB::transaction(function () use ($task, $file, $actor, $path) {
            $attachment = TaskAttachment::query()->create([
                'organization_id' => $task->organization_id,
                'task_id' => $task->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize() ?: 0,
                'uploaded_by' => $actor->id,
            ]);

            $this->auditLogger->log($task, 'uploaded', [
                'file' => $attachment->file_name,
                'attachment_id' => $attachment->id,
            ], $actor);

            app(WatcherService::class)->notifyWatchers(
                $task,
                'task.attachment_added',
                __(':actor uploaded :file to :task.', [
                    'actor' => $actor->name,
                    'file' => $attachment->file_name,
                    'task' => $task->title,
                ]),
                $actor,
                __('New attachment'),
            );

            return $attachment;
        });
    }

    public function download(TaskAttachment $attachment): StreamedResponse
    {
        if (! Storage::disk('public')->exists($attachment->file_path)) {
            throw ValidationException::withMessages([
                'attachment' => __('The attachment file could not be found.'),
            ]);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function delete(TaskAttachment $attachment, ?User $actor = null): void
    {
        if ($attachment->task?->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        if ($attachment->task) {
            $this->auditLogger->log($attachment->task, 'attachment_deleted', [
                'file' => $attachment->file_name,
                'attachment_id' => $attachment->id,
            ], $actor);
        }

        $attachment->delete();
    }
}
