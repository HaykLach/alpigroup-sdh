<?php

namespace App\Filament\Resources\Pim\PimQuotationResource\Pages;

use App\Filament\Resources\Pim\PimQuotationResource;
use App\Filament\Services\PimQuotationResourceService;
use App\Models\Pim\PimQuotation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePimQuotation extends CreateRecord
{
    protected static string $resource = PimQuotationResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        PimQuotationResourceService::stripSelectAssignmentFormData($data);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $id = $data['quotation_template_selector'];
        unset($data['quotation_template_selector']);

        $quotationTemplate = PimQuotationResourceService::getPimQuotationTemplate($id);

        $data['content'] = $quotationTemplate->content;
        $data['discount_percentage'] = $quotationTemplate->discount_percentage;
        $data['discount_amount'] = $quotationTemplate->discount_amount;
        $data['quotation_number'] = PimQuotation::generateQuotationNumber();

        /** @var PimQuotation $newQuotation */
        $newQuotation = static::getModel()::create($data);

        PimQuotationResourceService::assignQuotationProducts($quotationTemplate, $newQuotation);

        $newQuotation->refresh();

        return $newQuotation;
    }
}
