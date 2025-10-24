<?php

namespace App\Enums\Pim;

enum PimQuatationListingItemVisibility: int
{
    case PUBLIC = 1;
    case INTERNAL = 2;

    public static function asSelectArray(): array
    {
        return [
            self::PUBLIC->value => __('für Kunde sichtbar'),
            self::INTERNAL->value => __('nur für interne Benutzer sichtbar'),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getTextByValue(string $value): ?string
    {
        $array = self::asSelectArray();

        return $array[$value] ?? null;
    }
}
