<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogVendorResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogVendorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorCatalogVendor extends ViewRecord
{
    protected static string $resource = VendorCatalogVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
