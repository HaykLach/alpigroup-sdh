<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogVendorResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogVendorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorCatalogVendors extends ListRecords
{
    protected static string $resource = VendorCatalogVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
