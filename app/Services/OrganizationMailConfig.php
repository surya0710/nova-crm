<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Crypt;

class OrganizationMailConfig
{
    protected Organization $organization;

    protected array $config;

    public function for(Organization $organization): self
    {
        $clone = clone $this;
        $clone->organization = $organization;
        $stored = $organization->settings['mail'] ?? [];
        $clone->config = array_merge(
            config('organization_mail.defaults'),
            $stored
        );

        // Older settings only stored driver (e.g. log). Defaults include provider=smtp,
        // which would otherwise override an explicit log driver.
        if (! array_key_exists('provider', $stored) && filled($stored['driver'] ?? null)) {
            $clone->config['provider'] = $stored['driver'];
        }

        return $clone;
    }

    public function isConfigured(): bool
    {
        if (! ($this->config['enabled'] ?? false)) {
            return false;
        }

        if (empty($this->config['from_address']) || empty($this->config['from_name'])) {
            return false;
        }

        if ($this->driver() === 'smtp') {
            return filled($this->config['host'] ?? null)
                && filled($this->config['port'] ?? null);
        }

        return $this->driver() === 'log';
    }

    public function driver(): string
    {
        $provider = $this->provider();
        $providers = config('organization_mail.providers', []);

        if (isset($providers[$provider]['driver'])) {
            return $providers[$provider]['driver'];
        }

        return $this->config['driver'] ?? 'smtp';
    }

    public function provider(): string
    {
        return $this->config['provider'] ?? ($this->config['driver'] ?? 'smtp');
    }

    public function fromAddress(): ?Address
    {
        if (empty($this->config['from_address'])) {
            return null;
        }

        return new Address(
            $this->config['from_address'],
            $this->config['from_name'] ?? $this->organization->name,
        );
    }

    public function replyToAddress(): ?Address
    {
        $replyTo = trim((string) ($this->config['reply_to'] ?? ''));

        if ($replyTo === '' || ! filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return new Address($replyTo, $this->config['from_name'] ?? $this->organization->name);
    }

    public function signature(): ?string
    {
        $signature = trim((string) ($this->config['signature'] ?? ''));

        return $signature !== '' ? $signature : null;
    }

    /**
     * @return list<string>
     */
    public function defaultCc(): array
    {
        return ClientEmailCc::parse($this->config['default_cc'] ?? '');
    }

    /**
     * @return list<string>
     */
    public function defaultBcc(): array
    {
        return ClientEmailCc::parse($this->config['default_bcc'] ?? '');
    }

    public function displayFrom(): string
    {
        $from = $this->fromAddress();

        if (! $from) {
            return $this->organization->name;
        }

        return $from->name.' <'.$from->address.'>';
    }

    public function mailerName(): string
    {
        return 'organization_'.$this->organization->id;
    }

    public function registerMailer(): string
    {
        $name = $this->mailerName();

        if ($this->driver() === 'log') {
            config([
                'mail.mailers.'.$name => [
                    'transport' => 'log',
                    'channel' => null,
                ],
            ]);

            return $name;
        }

        $encryption = $this->config['encryption'] ?? 'tls';

        config([
            'mail.mailers.'.$name => [
                'transport' => 'smtp',
                'host' => $this->config['host'],
                'port' => (int) $this->config['port'],
                'encryption' => $encryption === 'none' ? null : $encryption,
                'username' => $this->config['username'] ?? null,
                'password' => $this->decryptedPassword(),
                'timeout' => null,
                'local_domain' => parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST),
            ],
        ]);

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSettingsArray(): array
    {
        return [
            'enabled' => (bool) ($this->config['enabled'] ?? false),
            'provider' => $this->provider(),
            'driver' => $this->driver(),
            'host' => $this->config['host'] ?? '',
            'port' => (int) ($this->config['port'] ?? 587),
            'encryption' => $this->config['encryption'] ?? 'tls',
            'username' => $this->config['username'] ?? '',
            'from_address' => $this->config['from_address'] ?? '',
            'from_name' => $this->config['from_name'] ?? '',
            'reply_to' => $this->config['reply_to'] ?? '',
            'default_cc' => $this->config['default_cc'] ?? '',
            'default_bcc' => $this->config['default_bcc'] ?? '',
            'signature' => $this->config['signature'] ?? '',
            'has_password' => filled($this->config['password'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function mergeSettings(array $existing, array $input): array
    {
        $merged = array_merge(config('organization_mail.defaults'), $existing);

        $provider = $input['mail_provider'] ?? $input['mail_driver'] ?? ($merged['provider'] ?? 'smtp');
        $providers = config('organization_mail.providers', []);

        if (! array_key_exists($provider, $providers)) {
            $provider = 'smtp';
        }

        $preset = $providers[$provider];

        $merged['enabled'] = filter_var($input['mail_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $merged['provider'] = $provider;
        $merged['driver'] = $preset['driver'] ?? ($input['mail_driver'] ?? $merged['driver']);
        $merged['host'] = $input['mail_host'] ?? $merged['host'];
        $merged['port'] = (int) ($input['mail_port'] ?? $merged['port']);
        $merged['encryption'] = $input['mail_encryption'] ?? $merged['encryption'];
        $merged['username'] = $input['mail_username'] ?? $merged['username'];
        $merged['from_address'] = $input['mail_from_address'] ?? $merged['from_address'];
        $merged['from_name'] = $input['mail_from_name'] ?? $merged['from_name'];
        $merged['reply_to'] = $input['mail_reply_to'] ?? ($merged['reply_to'] ?? '');
        $merged['default_cc'] = $input['mail_default_cc'] ?? ($merged['default_cc'] ?? '');
        $merged['default_bcc'] = $input['mail_default_bcc'] ?? ($merged['default_bcc'] ?? '');
        $merged['signature'] = $input['mail_signature'] ?? ($merged['signature'] ?? '');

        if (filled($input['mail_password'] ?? null)) {
            $merged['password'] = Crypt::encryptString($input['mail_password']);
        }

        return $merged;
    }

    protected function decryptedPassword(): ?string
    {
        $password = $this->config['password'] ?? null;

        if (! filled($password)) {
            return null;
        }

        try {
            return Crypt::decryptString($password);
        } catch (\Throwable) {
            return null;
        }
    }
}
