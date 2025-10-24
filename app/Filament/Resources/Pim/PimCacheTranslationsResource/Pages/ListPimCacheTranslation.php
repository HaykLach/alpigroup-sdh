<?php

namespace App\Filament\Resources\Pim\PimCacheTranslationsResource\Pages;

use App\Filament\Resources\Pim\PimCacheTranslationResource;
use Filament\Resources\Pages\ListRecords;

class ListPimCacheTranslation extends ListRecords
{
    protected static string $resource = PimCacheTranslationResource::class;

    public function getBreadcrumb(): string
    {
        return __('Liste');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
