<?php

namespace App\Services;

use App\Models\User;

class ClientEmailCc
{
    /**
     * @return list<string>
     */
    public static function parse(mixed $cc): array
    {
        if (is_array($cc)) {
            $emails = $cc;
        } else {
            $emails = preg_split('/[,;]+/', (string) $cc) ?: [];
        }

        return collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Merge the authenticated sender into CC while preserving manual recipients
     * and dropping duplicates of the To address.
     *
     * @return list<string>
     */
    public static function merge(?User $sender, string $to, mixed $manualCc = null): array
    {
        $toNorm = strtolower(trim($to));
        $senderEmail = strtolower(trim((string) $sender?->email));

        $cc = collect(self::parse($manualCc));

        if ($senderEmail !== '' && $senderEmail !== $toNorm && filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $cc->prepend($senderEmail);
        }

        return $cc
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && $email !== $toNorm && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
