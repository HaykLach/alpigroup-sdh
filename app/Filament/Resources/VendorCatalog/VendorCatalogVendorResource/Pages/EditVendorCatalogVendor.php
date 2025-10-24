<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogVendorResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogVendorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorCatalogVendor extends EditRecord
{
    protected static string $resource = VendorCatalogVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
