<?php

namespace App\Filament\Resources\Pim\PimQuotationResource\Pages;

use App\Filament\Resources\Pages\PimListRecords;
use App\Filament\Resources\Pim\PimQuotationResource;

class ListPimQuotations extends PimListRecords
{
    protected static string $resource = PimQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
