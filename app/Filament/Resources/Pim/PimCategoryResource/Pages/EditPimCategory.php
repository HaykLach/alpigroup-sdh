<?php

namespace App\Filament\Resources\Pim\PimCategoryResource\Pages;

use App\Filament\Resources\Pim\PimCategoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPimCategory extends EditRecord
{
    protected static string $resource = PimCategoryResource::class;

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
