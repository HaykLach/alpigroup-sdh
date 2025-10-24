<?php

namespace App\Filament\Widgets;

use App\Enums\Pim\PimQuotationStatus;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Models\Pim\PimQuotation;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class PimQuotationChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use PimWidgetUpdateAgentsTrait;
    use PimWidgetUpdateDateRangeTrait;
    use PimWidgetUpdateHeadingTrait;

    protected static ?int $sort = 20;

    protected static ?string $maxHeight = '400px';

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return $this->generateHeading(__('Angebote nach Status'), $this->getFilterStartDate(), $this->getFilterEndDate());
    }

    protected function getData(): array
    {
        $this->setFilterStartDate($this->filters['period']['startDate']);
        $this->setFilterEndDate($this->filters['period']['endDate']);
        $this->setFilterAgents($this->filters['agents']);

        // Prepare all months in the range for the chart
        $labels = [];
        $currentDate = Carbon::parse($this->getFilterStartDate())->startOfMonth();
        $lastDate = Carbon::parse($this->getFilterEndDate())->startOfMonth();

        while ($currentDate->lte($lastDate)) {
            $labels[] = $currentDate->format('M Y');
            $currentDate->addMonth();
        }

        // Create datasets for each status
        $datasets = [];
        $statuses = [
            PimQuotationStatus::DRAFT,
            PimQuotationStatus::SENT,
            PimQuotationStatus::ACCEPTED,
            PimQuotationStatus::DECLINED,
            PimQuotationStatus::EXPIRED,
        ];

        $user = auth()->user();

        /** @var PimQuotationStatus $status */
        foreach ($statuses as $status) {

            $query = PimQuotationResourceFormService::queryPerAgent(PimQuotation::query(), $user);
            $query = $this->applyFilterAgents($query);
            $query = $this->applyFilterDateRange($query);

            // Get quotations for this status within the date range
            $quotations = $query->where('status', $status)
                ->select(
                    DB::raw('DATE_FORMAT(updated_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Prepare data for this status
            $data = [];
            $currentDate = Carbon::parse($this->getFilterStartDate())->startOfMonth();

            while ($currentDate->lte($lastDate)) {
                $monthKey = $currentDate->format('Y-m');

                // Find count for this month or default to 0
                $monthData = $quotations->firstWhere('month', $monthKey);
                $data[] = $monthData ? $monthData->count : 0;

                $currentDate->addMonth();
            }

            // Add dataset for this status
            $datasets[] = [
                'label' => __($status->getLabel()),
                'data' => $data,
                'borderColor' => $status->getStatusColor($status),
                'backgroundColor' => $status->getStatusColor($status, 0.5),
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
