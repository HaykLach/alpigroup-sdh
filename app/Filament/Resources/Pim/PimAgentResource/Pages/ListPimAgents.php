<?php

namespace App\Filament\Resources\Pim\PimAgentResource\Pages;

use App\Filament\Resources\Pages\PimListRecords;
use App\Filament\Resources\Pim\PimAgentResource;
use App\Models\Pim\Customer\PimAgent;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPimAgents extends PimListRecords
{
    protected static string $resource = PimAgentResource::class;

    public function getBreadcrumb(): string
    {
        return __('Liste');
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make()
                ->label(__('Aktiv'))
                ->badge(fn () => PimAgent::active()->count()
                )
                ->modifyQueryUsing(fn (Builder $query) => $query->active()
                ),

            'inactive' => Tab::make()
                ->label(__('Inaktiv'))
                ->badge(fn () => PimAgent::blocked()->count()
                )
                ->modifyQueryUsing(fn (Builder $query) => $query->blocked()
                ),
        ];
    }
}
