<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogEntryResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorCatalogEntry extends CreateRecord
{
    protected static string $resource = VendorCatalogEntryResource::class;
}
