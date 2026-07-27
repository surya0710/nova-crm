<?php

namespace App\Services\Export\Writers;

use App\Models\ExportSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class XlsxExportWriter implements ExportWriterInterface
{
    protected ?Spreadsheet $spreadsheet = null;

    protected int $row = 1;

    protected string $relativePath = '';

    protected string $filename = '';

    protected ?Organization $organization = null;

    /** @var list<string> */
    protected array $headers = [];

    public function format(): string
    {
        return 'xlsx';
    }

    public function begin(ExportSession $session, Organization $organization, ?User $actor, array $headers): void
    {
        $this->organization = $organization;
        $this->headers = $headers;
        $this->spreadsheet = new Spreadsheet;
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Export');

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }
        $sheet->getStyle('A1:'.Coordinate::stringFromColumnIndex(count($headers)).'1')->getFont()->setBold(true);

        $this->row = 2;

        $disk = config('export.disk', 'local');
        $dir = trim((string) config('export.directory', 'exports'), '/');
        $this->filename = sprintf(
            '%s_export_%s_%s.xlsx',
            $session->entity_type,
            $session->id,
            now()->format('Ymd_His')
        );
        $this->relativePath = $dir.'/'.$organization->id.'/'.$this->filename;
        Storage::disk($disk)->makeDirectory($dir.'/'.$organization->id);
    }

    public function writeRow(array $values): void
    {
        if (! $this->spreadsheet) {
            throw new RuntimeException('XLSX writer is not open.');
        }

        $sheet = $this->spreadsheet->getActiveSheet();
        foreach (array_values($values) as $index => $value) {
            $sheet->setCellValue(
                [$index + 1, $this->row],
                is_scalar($value) || $value === null ? (string) ($value ?? '') : json_encode($value)
            );
        }
        $this->row++;

        // Free memory periodically for very large sheets.
        if ($this->row % 500 === 0) {
            $this->spreadsheet->garbageCollect();
        }
    }

    public function finish(): array
    {
        if (! $this->spreadsheet) {
            throw new RuntimeException('XLSX writer is not open.');
        }

        $disk = config('export.disk', 'local');
        $absolute = Storage::disk($disk)->path($this->relativePath);

        $writer = new Xlsx($this->spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($absolute);

        $this->spreadsheet->disconnectWorksheets();
        $this->spreadsheet = null;

        return [
            'path' => $this->relativePath,
            'mime' => config('export.formats.xlsx.mime'),
            'filename' => $this->filename,
            'size' => Storage::disk($disk)->size($this->relativePath),
        ];
    }
}
