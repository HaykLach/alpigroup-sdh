<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait PimWidgetUpdateDateRangeTrait
{
    public Carbon $filterStartDate;

    public Carbon $filterEndDate;

    public function setFilterStartDate(Carbon $date): void
    {
        $this->filterStartDate = $date;
    }

    public function setFilterEndDate(Carbon $date): void
    {
        $this->filterEndDate = $date;
    }

    public function getFilterStartDate(): Carbon
    {
        return $this->filterStartDate ?? Carbon::now()->subYear()->startOfDay();
    }

    public function getFilterEndDate(): Carbon
    {
        return $this->filterEndDate ?? Carbon::now()->endOfDay();
    }

    public function applyFilterDateRange(Builder $query): Builder
    {
        return $query->byDateRange($this->getFilterStartDate(), $this->getFilterEndDate());
    }
}
