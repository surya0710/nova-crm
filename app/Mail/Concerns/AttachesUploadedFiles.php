<?php

namespace App\Mail\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailables\Attachment;

trait AttachesUploadedFiles
{
    /** @var array<int, UploadedFile> */
    public array $uploadedAttachments = [];

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return array_map(function (UploadedFile $file) {
            return Attachment::fromPath($file->getRealPath())
                ->as($file->getClientOriginalName())
                ->withMime($file->getMimeType() ?? 'application/octet-stream');
        }, $this->uploadedAttachments);
    }
}
