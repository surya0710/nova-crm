<?php

namespace App\Services\Export\Writers;

use App\Models\ExportSession;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PdfExportWriter implements ExportWriterInterface
{
    /** @var list<string> */
    protected array $headers = [];

    /** @var list<list<mixed>> */
    protected array $rows = [];

    protected string $relativePath = '';

    protected string $filename = '';

    protected ?Organization $organization = null;

    protected ?User $actor = null;

    protected ?ExportSession $session = null;

    public function format(): string
    {
        return 'pdf';
    }

    public function begin(ExportSession $session, Organization $organization, ?User $actor, array $headers): void
    {
        $this->session = $session;
        $this->organization = $organization;
        $this->actor = $actor;
        $this->headers = $headers;
        $this->rows = [];

        $disk = config('export.disk', 'local');
        $dir = trim((string) config('export.directory', 'exports'), '/');
        $this->filename = sprintf(
            '%s_export_%s_%s.pdf',
            $session->entity_type,
            $session->id,
            now()->format('Ymd_His')
        );
        $this->relativePath = $dir.'/'.$organization->id.'/'.$this->filename;
        Storage::disk($disk)->makeDirectory($dir.'/'.$organization->id);
    }

    public function writeRow(array $values): void
    {
        $max = (int) config('export.pdf_max_rows', 2000);
        if (count($this->rows) >= $max) {
            throw new RuntimeException("PDF exports are limited to {$max} rows. Use CSV or Excel for larger datasets.");
        }

        $this->rows[] = array_map(
            static fn ($v) => is_scalar($v) || $v === null ? (string) ($v ?? '') : json_encode($v),
            array_values($values)
        );
    }

    public function finish(): array
    {
        if (! $this->organization || ! $this->session) {
            throw new RuntimeException('PDF writer is not open.');
        }

        $html = view('exports.pdf.table', [
            'organization' => $this->organization,
            'actor' => $this->actor,
            'session' => $this->session,
            'headers' => $this->headers,
            'rows' => $this->rows,
            'generatedAt' => now(),
        ])->render();

        $disk = config('export.disk', 'local');
        $absolute = Storage::disk($disk)->path($this->relativePath);

        Pdf::loadHTML($html)->setPaper('a4', 'landscape')->save($absolute);

        return [
            'path' => $this->relativePath,
            'mime' => config('export.formats.pdf.mime'),
            'filename' => $this->filename,
            'size' => Storage::disk($disk)->size($this->relativePath),
        ];
    }
}
