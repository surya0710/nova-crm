<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function store(Model $model, UploadedFile $file, ?User $user = null): Attachment
    {
        $organizationId = $model->organization_id ?? app(TenantContext::class)->id();
        $directory = 'attachments/'.$organizationId.'/'.str($model->getMorphClass())->classBasename()->lower()->value();

        $path = $file->store($directory, 'public');

        return DB::transaction(function () use ($model, $file, $user, $organizationId, $path) {
            $attachment = Attachment::query()->create([
                'organization_id' => $organizationId,
                'attachable_type' => $model->getMorphClass(),
                'attachable_id' => $model->getKey(),
                'uploaded_by' => $user?->id,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $this->auditLogger->log($model, 'uploaded', [
                'file' => $attachment->original_name,
            ], $user);

            return $attachment;
        });
    }

    public function delete(Attachment $attachment): void
    {
        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();
    }
}
