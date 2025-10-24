<?php

namespace App\Filament\Resources\Pim\PimMediaResource\Pages;

use App\Filament\Resources\Pim\PimMediaResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPimMedia extends EditRecord
{
    protected static string $resource = PimMediaResource::class;

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
