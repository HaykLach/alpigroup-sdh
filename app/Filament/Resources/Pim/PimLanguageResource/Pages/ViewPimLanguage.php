<?php

namespace App\Filament\Resources\Pim\PimLanguageResource\Pages;

use App\Filament\Resources\Pim\PimLanguageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPimLanguage extends ViewRecord
{
    protected static string $resource = PimLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
