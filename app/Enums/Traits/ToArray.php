<?php

namespace App\Enums\Traits;

trait ToArray
{
    /**
     * Get all enum values as an array of options for select boxes.
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return array_reduce(
            self::cases(),
            function (array $options, self $enum) {
                // If the enum has a getLabel method, use it for the option label
                if (method_exists($enum, 'getLabel')) {
                    $options[$enum->value] = $enum->getLabel();
                } else {
                    // Otherwise, use the enum value as the label
                    $options[$enum->value] = $enum->value;
                }

                return $options;
            },
            []
        );
    }
}
