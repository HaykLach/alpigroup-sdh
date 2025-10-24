<?php

namespace App\Filament\Widgets;

use App\Enums\Pim\PimQuotationStatus;
use App\Filament\Resources\Pim\PimQuotationResource;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Models\Pim\PimQuotation;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PimQuotationOverviewWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use PimWidgetUpdateAgentsTrait;
    use PimWidgetUpdateDateRangeTrait;
    use PimWidgetUpdateHeadingTrait;

    public function getHeading(): string
    {
        return $this->generateHeading(__('Angebote Überblick'), $this->getFilterStartDate(), $this->getFilterEndDate());
    }

    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        $user = auth()->user();

        $stats = [];

        $this->setFilterStartDate($this->filters['period']['startDate']);
        $this->setFilterEndDate($this->filters['period']['endDate']);
        $this->setFilterAgents($this->filters['agents']);

        $startDate = $this->getFilterStartDate();
        $days = $this->getFilterEndDate()->diffInDays($startDate);

        $dailyCounts = [];
        $dailyStatusCounts = [];

        foreach (PimQuotationStatus::cases() as $status) {
            $dailyStatusCounts[$status->value] = [];
        }

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $dailyCounts[$date] = 0;

            foreach (PimQuotationStatus::cases() as $status) {
                $dailyStatusCounts[$status->value][$date] = 0;
            }
        }

        $query = PimQuotation::query();
        $query = PimQuotationResourceFormService::queryPerAgent($query, $user);
        $query = $this->applyFilterAgents($query);
        $query = $this->applyFilterDateRange($query);

        foreach ($query->get() as $quotation) {
            $date = $quotation->updated_at->format('Y-m-d');
            if (isset($dailyCounts[$date])) {
                $dailyCounts[$date]++;
            }

            if (isset($dailyStatusCounts[$quotation->status->value][$date])) {
                $dailyStatusCounts[$quotation->status->value][$date]++;
            }
        }

        $statusCounts = $query
            ->where('updated_at', '>=', $startDate)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalCount = array_sum($statusCounts);

        $stats[] = Stat::make(__('Angebote gesamt'), $totalCount)
            ->color('gray')
            ->chart(array_values($dailyCounts))
            ->url(PimQuotationResource::getUrl());

        foreach (PimQuotationStatus::cases() as $status) {
            $count = $statusCounts[$status->value] ?? 0;
            $stats[] = Stat::make($status->getLabel(), $count)
                ->color($status->getColor())
                ->chart(array_values($dailyStatusCounts[$status->value] ?? []))
                ->url(PimQuotationResource::getUrl().'?tableFilters[status][values][]='.$status->value);
        }

        return $stats;
    }
}
