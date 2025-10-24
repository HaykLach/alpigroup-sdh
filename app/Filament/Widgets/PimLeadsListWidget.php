<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pages\Traits\CanResizeColumnsPerUser;
use App\Filament\Resources\Pages\Traits\CanToggleColumnsPerUser;
use App\Filament\Resources\Pim\PimLeadResource;
use App\Filament\Services\PimLeadResourceFormService;
use App\Filament\Services\PimQuotationResourceFormService;
use App\Models\Pim\PimLead;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class PimLeadsListWidget extends TableWidget
{
    use CanResizeColumnsPerUser;
    use CanToggleColumnsPerUser;
    use InteractsWithPageFilters;
    use PimWidgetUpdateAgentsTrait;
    use PimWidgetUpdateHeadingTrait;

    protected static ?int $sort = 15;

    protected int|string|array $columnSpan = 'full';

    public static function getModel(): string
    {
        return PimLead::class;
    }

    public function mount(): void
    {
        $this->mountCanToggleColumnsPerUser();
    }

    public function table(Table $table): Table
    {
        $table = PimLeadResourceFormService::getTable($table)
            ->actions([
                EditAction::make()
                    ->label(__('bearbeiten')),
            ])
            ->recordUrl(fn (PimLead $record): string => PimLeadResource::getUrl('edit', ['record' => $record]));

        $user = auth()->user();

        $query = PimLeadResource::getEloquentQuery();
        $query = PimQuotationResourceFormService::queryPerAgent($query, $user);
        $query = $this->applyFilterAgents($query);

        return $table
            ->heading(__('Leads Liste'))
            ->query($query);
    }
}
