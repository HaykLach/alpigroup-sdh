<?php

namespace App\Filament\Resources\Pim\PimQuotationTemplateResource\Pages;

use App\Filament\Resources\Pim\PimQuotationTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePimQuotationTemplate extends CreateRecord
{
    protected static string $resource = PimQuotationTemplateResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
