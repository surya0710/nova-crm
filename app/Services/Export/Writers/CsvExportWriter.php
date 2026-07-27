<?php

namespace App\Services\Export\Writers;

use App\Models\ExportSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CsvExportWriter implements ExportWriterInterface
{
    /** @var resource|null */
    protected $handle = null;

    protected string $relativePath = '';

    protected string $filename = '';

    public function format(): string
    {
        return 'csv';
    }

    public function begin(ExportSession $session, Organization $organization, ?User $actor, array $headers): void
    {
        $disk = config('export.disk', 'local');
        $dir = trim((string) config('export.directory', 'exports'), '/');
        $this->filename = sprintf(
            '%s_export_%s_%s.csv',
            $session->entity_type,
            $session->id,
            now()->format('Ymd_His')
        );
        $this->relativePath = $dir.'/'.$organization->id.'/'.$this->filename;

        Storage::disk($disk)->makeDirectory($dir.'/'.$organization->id);
        $absolute = Storage::disk($disk)->path($this->relativePath);

        $this->handle = fopen($absolute, 'wb');
        if ($this->handle === false) {
            throw new RuntimeException('Unable to open CSV export file for writing.');
        }

        fwrite($this->handle, "\xEF\xBB\xBF");
        fputcsv($this->handle, $headers);
    }

    public function writeRow(array $values): void
    {
        if (! is_resource($this->handle)) {
            throw new RuntimeException('CSV writer is not open.');
        }

        fputcsv($this->handle, array_map(
            static fn ($v) => is_scalar($v) || $v === null ? (string) ($v ?? '') : json_encode($v),
            $values
        ));
    }

    public function finish(): array
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
            $this->handle = null;
        }

        $disk = config('export.disk', 'local');
        $size = Storage::disk($disk)->size($this->relativePath);

        return [
            'path' => $this->relativePath,
            'mime' => config('export.formats.csv.mime'),
            'filename' => $this->filename,
            'size' => $size,
        ];
    }
}
