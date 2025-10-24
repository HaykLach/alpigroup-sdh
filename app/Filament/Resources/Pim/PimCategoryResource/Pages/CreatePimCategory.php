<?php

namespace App\Filament\Resources\Pim\PimCategoryResource\Pages;

use App\Filament\Resources\Pim\PimCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePimCategory extends CreateRecord
{
    protected static string $resource = PimCategoryResource::class;

    public function getTitle(): string
    {
        return __('Kategorie');
    }
}
