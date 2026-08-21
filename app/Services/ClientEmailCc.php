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
        return self::resolve($sender, $to, $manualCc, ccSender: true)['cc'];
    }

    /**
     * @param  list<string>|string|null  $defaultCc
     * @param  list<string>|string|null  $defaultBcc
     * @return array{to: list<string>, cc: list<string>, bcc: list<string>}
     */
    public static function resolve(
        ?User $sender,
        mixed $to,
        mixed $cc = null,
        mixed $bcc = null,
        mixed $defaultCc = [],
        mixed $defaultBcc = [],
        bool $ccSender = false,
    ): array {
        $toList = self::parse($to);
        $toSet = array_flip($toList);

        $ccList = collect(self::parse($cc))->merge(self::parse($defaultCc));
        $senderEmail = strtolower(trim((string) $sender?->email));

        if (
            $ccSender
            && $senderEmail !== ''
            && filter_var($senderEmail, FILTER_VALIDATE_EMAIL)
            && ! isset($toSet[$senderEmail])
        ) {
            $ccList = $ccList->prepend($senderEmail);
        }

        $ccList = $ccList
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && ! isset($toSet[$email]) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        $ccSet = array_flip($ccList->all());

        $bccList = collect(self::parse($bcc))
            ->merge(self::parse($defaultBcc))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== ''
                && ! isset($toSet[$email])
                && ! isset($ccSet[$email])
                && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        return [
            'to' => $toList,
            'cc' => $ccList->all(),
            'bcc' => $bccList->all(),
        ];
    }
}
