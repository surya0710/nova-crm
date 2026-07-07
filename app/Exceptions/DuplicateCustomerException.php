<?php

namespace App\Exceptions;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DuplicateCustomerException extends ValidationException
{
    /**
     * @param  Collection<int, Customer>  $duplicateCustomers
     */
    public function __construct(public readonly Collection $duplicateCustomers)
    {
        $validator = Validator::make([], []);
        $validator->errors()->add(
            'duplicate_customer',
            __('A customer with this email or phone already exists.')
        );

        parent::__construct($validator);
    }
}
