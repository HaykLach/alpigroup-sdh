<?php

namespace App\Filament\Resources\Pim\PimMediaResource\Pages;

use App\Filament\Resources\Pim\PimMediaResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPimMedia extends ListRecords
{
    protected static string $resource = PimMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
