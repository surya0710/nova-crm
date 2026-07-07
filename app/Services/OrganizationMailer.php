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

    public function send(Organization $organization, string $recipient, Mailable $mailable): void
    {
        $config = $this->mailConfig->for($organization);

        if (! $config->isConfigured()) {
            throw new \RuntimeException(__('Organization email is not configured. Set up SMTP in Organization Settings → Email.'));
        }

        $mailer = $config->registerMailer();

        Mail::mailer($mailer)->to($recipient)->send($mailable);
    }
}
