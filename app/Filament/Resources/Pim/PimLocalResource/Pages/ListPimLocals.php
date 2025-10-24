<?php

namespace App\Filament\Resources\Pim\PimLocalResource\Pages;

use App\Filament\Resources\Pim\PimLocalResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPimLocals extends ListRecords
{
    protected static string $resource = PimLocalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
