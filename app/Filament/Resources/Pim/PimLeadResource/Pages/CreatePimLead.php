<?php

namespace App\Filament\Resources\Pim\PimLeadResource\Pages;

use App\Enums\Pim\PimLeadSourceNamespace;
use App\Filament\Resources\Pim\PimLeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePimLead extends CreateRecord
{
    protected static string $resource = PimLeadResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return __('Lead erstellen');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source_namespace'] = PimLeadSourceNamespace::SDH;

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(__('speichern'))
                ->icon('heroicon-o-document'),
            $this->getCancelFormAction()
                ->label(__('verwerfen')),
        ];
    }
}
