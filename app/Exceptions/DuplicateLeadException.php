<?php

namespace App\Exceptions;

use App\Models\Lead;
use Exception;

class DuplicateLeadException extends Exception
{
    public function __construct(public readonly Lead $lead)
    {
        parent::__construct(__('A lead with this email or phone already exists.'));
    }
}
