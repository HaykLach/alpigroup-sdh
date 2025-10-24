<?php

namespace App\Filament\Resources\Pim\PimLanguageResource\Pages;

use App\Filament\Resources\Pim\PimLanguageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPimLanguage extends EditRecord
{
    protected static string $resource = PimLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
