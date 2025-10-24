<?php

namespace App\Filament\Resources\Pim\PimCategoryResource\Pages;

use App\Filament\Resources\Pim\PimCategoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPimCategory extends ViewRecord
{
    protected static string $resource = PimCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
