<?php

namespace App\Filament\Resources\Pim\PimLeadResource\Pages;

use App\Enums\Pim\PimLeadStatus;
use App\Filament\Resources\Pages\PimListRecords;
use App\Filament\Resources\Pim\PimLeadResource;
use App\Models\Pim\PimLead;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPimLead extends PimListRecords
{
    protected static string $resource = PimLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('erstellen'))
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('Liste');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label(__('Alle'))
                ->badge(fn () => PimLead::count())
                ->modifyQueryUsing(fn (Builder $query) => $query),

            'open' => Tab::make()
                ->label(PimLeadStatus::OPEN->getLabel())
                ->badge(fn () => PimLead::where('status', PimLeadStatus::OPEN)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PimLeadStatus::OPEN)),

            'in_progress' => Tab::make()
                ->label(PimLeadStatus::IN_PROGRESS->getLabel())
                ->badge(fn () => PimLead::where('status', PimLeadStatus::IN_PROGRESS)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PimLeadStatus::IN_PROGRESS)),

            'completed' => Tab::make()
                ->label(PimLeadStatus::COMPLETED->getLabel())
                ->badge(fn () => PimLead::where('status', PimLeadStatus::COMPLETED)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PimLeadStatus::COMPLETED)),
        ];
    }
}
