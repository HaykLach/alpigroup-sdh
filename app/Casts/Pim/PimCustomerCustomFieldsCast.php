<?php

namespace App\Casts\Pim;

use App\Enums\Pim\PimCustomerCustomFields;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PimCustomerCustomFieldsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        $data = json_decode($value, true) ?? [];

        // Ensure all enum keys are present with their values or null
        $result = [];
        foreach (PimCustomerCustomFields::cases() as $case) {
            $result[$case->value] = $data[$case->value] ?? null;
        }

        return $result;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string|false
    {
        // If $value is already a string (JSON), return it as is
        if (is_string($value)) {
            return $value;
        }

        // Otherwise, encode the array to JSON
        return json_encode($value);
    }
}
