<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogEntryResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogEntryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorCatalogEntries extends ListRecords
{
    protected static string $resource = VendorCatalogEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
