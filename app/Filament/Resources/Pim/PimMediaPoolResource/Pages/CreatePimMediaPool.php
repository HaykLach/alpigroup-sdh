<?php

namespace App\Filament\Resources\Pim\PimMediaPoolResource\Pages;

use App\Filament\Resources\Pim\PimMediaPoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePimMediaPool extends CreateRecord
{
    protected static string $resource = PimMediaPoolResource::class;

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
