<?php

namespace App\Enums\Pim;

use App\Enums\Traits\ToArray;

enum PimQuatationValidityPeriodUnit: string
{
    use toArray;

    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    public function getLabel(): string
    {
        return match ($this) {
            self::DAY => __('Tag(e)'),
            self::WEEK => __('Woche(n)'),
            self::MONTH => __('Monat(e)'),
            self::YEAR => __('Jahr(e)'),
        };
    }
}
