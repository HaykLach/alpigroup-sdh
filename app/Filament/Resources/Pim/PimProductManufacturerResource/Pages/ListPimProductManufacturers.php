<?php

namespace App\Filament\Resources\Pim\PimProductManufacturerResource\Pages;

use App\Filament\Resources\Pim\PimProductManufacturerResource;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Pim\PimProductManufacturerTranslationService;
use App\Services\Pim\PimResourceService;
use App\Services\Pim\PimTranslationService;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPimProductManufacturers extends ListRecords
{
    protected static string $resource = PimProductManufacturerResource::class;

    public function getBreadcrumb(): string
    {
        return __('Liste');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('hinzufügen'))
                ->form(PimProductManufacturerResource::getFormElements(false))
                ->using(function (array $data) {
                    PimResourceService::stripProvidedFormData($data);

                    $manufacturer = PimProductManufacturer::create($data);

                    // add translations
                    $translationService = new PimTranslationService;
                    $otherLanguages = $translationService->getExtraLanguages();

                    PimProductManufacturerTranslationService::addInitialTranslations($manufacturer, $otherLanguages);

                    return $manufacturer;
                }),
        ];
    }
}
