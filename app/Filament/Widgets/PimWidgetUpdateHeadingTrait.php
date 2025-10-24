<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;

trait PimWidgetUpdateHeadingTrait
{
    public function generateHeading(?string $prefix, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        return $prefix.' ('.$startDate->format('d.m.Y').' - '.$endDate->format('d.m.Y').')';
    }
}
