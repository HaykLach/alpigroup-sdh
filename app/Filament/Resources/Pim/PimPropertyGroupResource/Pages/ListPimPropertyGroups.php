<?php

namespace App\Filament\Resources\Pim\PimPropertyGroupResource\Pages;

use App\Filament\Resources\Pim\PimPropertyGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPimPropertyGroups extends ListRecords
{
    protected static string $resource = PimPropertyGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->form(PimPropertyGroupResource::getFormElements(false)),
        ];
    }
}
