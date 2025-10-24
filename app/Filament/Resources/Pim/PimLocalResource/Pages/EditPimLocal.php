<?php

namespace App\Filament\Resources\Pim\PimLocalResource\Pages;

use App\Filament\Resources\Pim\PimLocalResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPimLocal extends EditRecord
{
    protected static string $resource = PimLocalResource::class;

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
