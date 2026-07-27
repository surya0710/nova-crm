<?php

namespace App\Services\Export\Writers;

use App\Models\ExportSession;
use App\Models\Organization;
use App\Models\User;

interface ExportWriterInterface
{
    public function format(): string;

    /**
     * Open a writable target and write the header row.
     *
     * @param  list<string>  $headers
     */
    public function begin(ExportSession $session, Organization $organization, ?User $actor, array $headers): void;

    /**
     * @param  list<mixed>  $values  Ordered cell values matching headers
     */
    public function writeRow(array $values): void;

    /**
     * Finalize the file and return absolute path on the configured disk.
     *
     * @return array{path: string, mime: string, filename: string, size: int}
     */
    public function finish(): array;
}
