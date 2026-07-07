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
        $clone->config = array_merge(
            config('organization_mail.defaults'),
            $organization->settings['mail'] ?? []
        );

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
        return $this->config['driver'] ?? 'smtp';
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
            'driver' => $this->config['driver'] ?? 'smtp',
            'host' => $this->config['host'] ?? '',
            'port' => (int) ($this->config['port'] ?? 587),
            'encryption' => $this->config['encryption'] ?? 'tls',
            'username' => $this->config['username'] ?? '',
            'from_address' => $this->config['from_address'] ?? '',
            'from_name' => $this->config['from_name'] ?? '',
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

        $merged['enabled'] = filter_var($input['mail_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $merged['driver'] = $input['mail_driver'] ?? $merged['driver'];
        $merged['host'] = $input['mail_host'] ?? $merged['host'];
        $merged['port'] = (int) ($input['mail_port'] ?? $merged['port']);
        $merged['encryption'] = $input['mail_encryption'] ?? $merged['encryption'];
        $merged['username'] = $input['mail_username'] ?? $merged['username'];
        $merged['from_address'] = $input['mail_from_address'] ?? $merged['from_address'];
        $merged['from_name'] = $input['mail_from_name'] ?? $merged['from_name'];

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
