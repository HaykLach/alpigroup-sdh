<?php

namespace App\Filament\Resources\VendorCatalog\VendorCatalogImportRuleResource\Pages;

use App\Filament\Resources\VendorCatalog\VendorCatalogImportRuleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorCatalogImportRules extends ListRecords
{
    protected static string $resource = VendorCatalogImportRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
