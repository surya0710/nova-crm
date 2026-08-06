<?php

namespace App\Http\Requests\Concerns;

use Closure;

trait ValidatesGeographicFilters
{
    /**
     * @return array<string, mixed>
     */
    protected function geographicFilterRules(): array
    {
        $stringOrArray = static function (string $attribute, mixed $value, Closure $fail): void {
            $values = is_array($value) ? $value : [$value];

            foreach ($values as $item) {
                if (! is_string($item) || mb_strlen(trim($item)) > 255) {
                    $fail(__('The :attribute field must contain text values no longer than 255 characters.'));

                    return;
                }
            }
        };

        return [
            'state' => ['sometimes', 'nullable', $stringOrArray],
            'country' => ['sometimes', 'nullable', $stringOrArray],
        ];
    }
}
