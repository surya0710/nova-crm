<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Soft empty-state signal when a user has access but no linked employee record.
 * Must not be treated as an authorization failure (403).
 */
class MissingEmployeeRecordException extends Exception
{
    public function __construct(
        public readonly string $audience = 'employee',
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : $this->defaultMessage());
    }

    public function defaultMessage(): string
    {
        return match ($this->audience) {
            'manager' => __('No employees assigned.'),
            'hr' => __('No employee records available.'),
            'supervisor' => __('No team members found.'),
            default => __('No employee record is linked to your account.'),
        };
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response([
                'message' => $this->getMessage(),
                'empty_state' => true,
                'audience' => $this->audience,
            ], 200);
        }

        return response()->view('hrms.empty-states.missing-employee', [
            'audience' => $this->audience,
            'message' => $this->getMessage(),
            'title' => match ($this->audience) {
                'manager' => __('Manager Dashboard'),
                'hr' => __('HR'),
                'supervisor' => __('Team'),
                default => __('Self-Service'),
            },
        ], 200);
    }
}
