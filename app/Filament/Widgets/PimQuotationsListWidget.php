<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pages\Traits\CanResizeColumnsPerUser;
use App\Filament\Resources\Pages\Traits\CanToggleColumnsPerUser;
use App\Filament\Resources\Pim\PimQuotationResource;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Models\Pim\PimQuotation;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class PimQuotationsListWidget extends TableWidget
{
    use CanResizeColumnsPerUser;
    use CanToggleColumnsPerUser;
    use InteractsWithPageFilters;
    use PimWidgetUpdateAgentsTrait;
    use PimWidgetUpdateDateRangeTrait;
    use PimWidgetUpdateHeadingTrait;

    protected static ?int $sort = 15;

    protected int|string|array $columnSpan = 'full';

    public static function getModel(): string
    {
        return PimQuotation::class;
    }

    public function mount(): void
    {
        $this->mountCanToggleColumnsPerUser();
    }

    public function table(Table $table): Table
    {
        $this->setFilterStartDate($this->filters['period']['startDate']);
        $this->setFilterEndDate($this->filters['period']['endDate']);
        $this->setFilterAgents($this->filters['agents']);

        $table = PimQuotationResourceFormService::getTable($table)
            ->actions([
                EditAction::make()
                    ->label(__('bearbeiten'))
                    ->url(fn (PimQuotation $record): string => PimQuotationResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn (PimQuotation $record): string => PimQuotationResource::getUrl('edit', ['record' => $record]));

        $user = auth()->user();

        $query = PimQuotationResource::getEloquentQuery();
        $query = PimQuotationResourceFormService::queryPerAgent($query, $user);
        $query = $this->applyFilterAgents($query);
        $query = $this->applyFilterDateRange($query);

        return $table
            ->heading($this->generateHeading(__('Angebote Liste'), $this->getFilterStartDate(), $this->getFilterEndDate()))
            ->defaultGroup('quotation_number')
            ->query($query);
    }
}
