<?php

namespace App\Filament\Resources\Pim\PimAgentResource\Pages;

use App\Enums\Pim\PimCustomerType;
use App\Filament\Resources\Pim\PimAgentResource;
use App\Services\Pim\PimResourceCustomerService;
use Filament\Resources\Pages\EditRecord;

class EditPimAgent extends EditRecord
{
    protected static string $resource = PimAgentResource::class;

    public function mutateFormDataBeforeSave(array $data): array
    {
        return PimResourceCustomerService::setCustomFieldTypeData($data, PimCustomerType::AGENT);
    }
}
