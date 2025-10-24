<?php

namespace App\Filament\Resources\Pim\PimMediaPoolResource\Pages;

use App\Filament\Resources\Pim\PimMediaPoolResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPimMediaPool extends EditRecord
{
    protected static string $resource = PimMediaPoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('entfernen'))
                ->visible(function ($record) {
                    if ($record->type === 'folder') {
                        return true;
                    } else {
                        return PimMediaPoolResource::countFilesInFolder($record->id) === 0;
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', [
            'parent_id' => $this->record->parent_id,
        ]);
    }

    public function getBreadcrumbs(): array
    {
        return PimMediaPoolResource::getBreadcrumbs();
    }
}
