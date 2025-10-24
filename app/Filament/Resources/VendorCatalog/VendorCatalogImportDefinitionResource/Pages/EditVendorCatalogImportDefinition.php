<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogImportDefinitionResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogImportDefinitionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorCatalogImportDefinition extends EditRecord
{
    protected static string $resource = VendorCatalogImportDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
