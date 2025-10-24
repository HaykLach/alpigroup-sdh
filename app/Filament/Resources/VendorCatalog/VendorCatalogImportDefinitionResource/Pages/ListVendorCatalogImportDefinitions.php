<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogImportDefinitionResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogImportDefinitionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorCatalogImportDefinitions extends ListRecords
{
    protected static string $resource = VendorCatalogImportDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
