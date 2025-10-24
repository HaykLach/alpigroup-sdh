<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogImportResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogImportResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorCatalogImport extends EditRecord
{
    protected static string $resource = VendorCatalogImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
