<?php

namespace App\Filament\Resources\Pim\PimCategoryResource\Pages;

use App\Filament\Resources\Pim\PimCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPimCategories extends ListRecords
{
    protected static string $resource = PimCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('erstellen'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
