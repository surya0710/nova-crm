<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class OrganizationMailer
{
    public function __construct(protected OrganizationMailConfig $mailConfig) {}

    public function isConfigured(Organization $organization): bool
    {
        return $this->mailConfig->for($organization)->isConfigured();
    }

    /**
     * @param  string|list<string>  $recipient
     * @param  list<string>  $cc
     * @param  list<string>  $bcc
     */
    public function send(Organization $organization, string|array $recipient, Mailable $mailable, array $cc = [], array $bcc = []): void
    {
        $config = $this->mailConfig->for($organization);

        if (! $config->isConfigured()) {
            throw new \RuntimeException(__('Organization email is not configured. Set up SMTP in Organization Settings → Email.'));
        }

        $mailer = $config->registerMailer();

        if ($cc !== []) {
            $mailable->cc($cc);
        }

        if ($bcc !== []) {
            $mailable->bcc($bcc);
        }

        Mail::mailer($mailer)->to($recipient)->send($mailable);
    }
}
