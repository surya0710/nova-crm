<?php

namespace App\Http\Requests\Careers;

use App\Models\Organization;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CandidateLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(Organization $organization): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => strtolower($this->string('email')->toString()),
            'password' => $this->string('password')->toString(),
            'organization_id' => $organization->id,
        ];

        if (! Auth::guard('candidate')->attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($organization));

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($organization));
    }

    public function ensureIsNotRateLimited(): void
    {
        $organization = $this->route('organization');

        if (! RateLimiter::tooManyAttempts($this->throttleKey($organization), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey($organization));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(Organization $organization): string
    {
        return Str::transliterate(
            'candidate|'.$organization->id.'|'.Str::lower($this->string('email')).'|'.$this->ip(),
        );
    }
}
