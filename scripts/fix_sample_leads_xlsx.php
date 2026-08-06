<?php

/**
 * Fixes public/Sample leads to import.xlsx so it passes lead import validation.
 *
 * Usage: php scripts/fix_sample_leads_xlsx.php
 */

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$sourcePath = __DIR__.'/../public/Sample leads to import.xlsx';
$outputPath = $argv[1] ?? $sourcePath;

$statusMap = [
    'dead' => 'Lost',
    'sign up' => 'New',
    'future prospects' => 'Contacted',
    'not connected' => 'Contacted',
    'ringing' => 'Contacted',
    'spoken' => 'Contacted',
    'msg sent' => 'Contacted',
    'hot lead' => 'Qualified',
];

$sourceMap = [
    'google' => 'Google Ads',
    'meta ads' => 'Facebook',
    'refrence' => 'Referral',
    'reference' => 'Referral',
    'creme' => 'Import',
    'latika' => 'Import',
    'naveen sir' => 'Import',
    'naveen leed' => 'Import',
    'study' => 'Import',
];

function mapValue(string $value, array $map): string
{
    $key = strtolower(trim($value));

    return $map[$key] ?? $value;
}

function normalizePhone(?string $raw): ?string
{
    if ($raw === null || trim($raw) === '') {
        return null;
    }

    $value = trim($raw);

    if (str_contains($value, ',')) {
        $value = trim(explode(',', $value)[0]);
    }

    if (str_contains($value, '/')) {
        $value = trim(explode('/', $value)[0]);
    }

    $value = preg_replace('/\s+/', '', $value) ?? $value;

    $digits = preg_replace('/\D+/', '', $value) ?? '';

    if ($digits === '') {
        return null;
    }

    if (strlen($digits) < 7 || strlen($digits) > 15) {
        return null;
    }

    return $digits;
}

function splitName(string $fullName): array
{
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? $fullName);

    if ($fullName === '' || strcasecmp($fullName, 'no name') === 0 || strcasecmp($fullName, 'user') === 0) {
        return ['', ''];
    }

    $parts = explode(' ', $fullName, 2);

    return [$parts[0], $parts[1] ?? ''];
}

$spreadsheet = IOFactory::load($sourcePath);
$sheet = $spreadsheet->getSheetByName('Lead Import');

if ($sheet === null) {
    fwrite(STDERR, "Lead Import sheet not found.\n");
    exit(1);
}

$highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
if ($highestColumnIndex > 9) {
    $sheet->removeColumnByIndex(10, $highestColumnIndex - 9);
}

$maxRow = $sheet->getHighestRow();
$seenPhones = [];
$stats = [
    'status_mapped' => 0,
    'source_mapped' => 0,
    'names_filled' => 0,
    'phones_normalized' => 0,
    'phones_deduped' => 0,
    'phones_cleared' => 0,
    'names_split' => 0,
];

for ($row = 2; $row <= $maxRow; $row++) {
    $firstName = trim((string) $sheet->getCell([1, $row])->getValue());
    $lastName = trim((string) $sheet->getCell([2, $row])->getValue());
    $phoneRaw = trim((string) $sheet->getCell([4, $row])->getValue());
    $status = trim((string) $sheet->getCell([6, $row])->getValue());
    $source = trim((string) $sheet->getCell([7, $row])->getValue());

    if ($firstName !== '' && $lastName === '') {
        [$splitFirst, $splitLast] = splitName($firstName);
        if ($splitLast !== '') {
            $sheet->setCellValue([1, $row], $splitFirst);
            $sheet->setCellValue([2, $row], $splitLast);
            $firstName = $splitFirst;
            $lastName = $splitLast;
            $stats['names_split']++;
        }
    }

    if ($status !== '') {
        $mappedStatus = mapValue($status, $statusMap);
        if ($mappedStatus !== $status) {
            $sheet->setCellValue([6, $row], $mappedStatus);
            $stats['status_mapped']++;
        }
    }

    if ($source !== '') {
        $mappedSource = mapValue($source, $sourceMap);
        if ($mappedSource !== $source) {
            $sheet->setCellValue([7, $row], $mappedSource);
            $stats['source_mapped']++;
        }
    }

    $phone = normalizePhone($phoneRaw);
    if ($phoneRaw !== '' && $phone !== $phoneRaw) {
        $stats['phones_normalized']++;
    }

    if ($phone !== null) {
        if (isset($seenPhones[$phone])) {
            $sheet->setCellValue([4, $row], null);
            $stats['phones_deduped']++;
            $phone = null;
        } else {
            $seenPhones[$phone] = $row;
            $sheet->setCellValue([4, $row], $phone);
        }
    } elseif ($phoneRaw !== '') {
        $sheet->setCellValue([4, $row], null);
        $stats['phones_cleared']++;
    }

    $firstName = trim((string) $sheet->getCell([1, $row])->getValue());
    $lastName = trim((string) $sheet->getCell([2, $row])->getValue());

    if ($firstName === '' && $lastName === '') {
        if ($phone !== null) {
            $sheet->setCellValue([1, $row], 'Lead');
            $sheet->setCellValue([2, $row], substr($phone, -4));
            $stats['names_filled']++;
        } else {
            $sheet->setCellValue([1, $row], 'Unknown');
            $sheet->setCellValue([2, $row], 'Lead');
            $stats['names_filled']++;
        }
    }
}

$writer = new Xlsx($spreadsheet);
$tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sample-leads-import-fixed-'.uniqid('', true).'.xlsx';
$writer->save($tempPath);
$spreadsheet->disconnectWorksheets();

$writtenPath = $outputPath;

if (! @copy($tempPath, $outputPath)) {
    $fallbackPath = dirname($sourcePath).DIRECTORY_SEPARATOR.'Sample leads to import.fixed.xlsx';
    if (@copy($tempPath, $fallbackPath)) {
        $writtenPath = $fallbackPath;
        fwrite(STDERR, "Could not overwrite {$outputPath} (file may be open in Excel).\n");
        fwrite(STDERR, "Wrote fixed copy to: {$fallbackPath}\n");
        fwrite(STDERR, "Close Excel, then run: php scripts/fix_sample_leads_xlsx.php\n");
    } else {
        @unlink($tempPath);
        fwrite(STDERR, "Could not write fixed import file.\n");
        exit(1);
    }
}

@unlink($tempPath);

echo "Fixed: {$writtenPath}\n";
foreach ($stats as $key => $value) {
    echo "  {$key}: {$value}\n";
}
