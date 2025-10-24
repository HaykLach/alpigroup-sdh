<?php

namespace App\Services\Export;

use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Property\PimPropertyGroup;
use Exception;
use Illuminate\Support\Collection;
use SmartDato\SdhShopwareSdk\DataTransferObjects\PropertyGroup;
use SmartDato\SdhShopwareSdk\Enums\PropertyGroupType;

class PimPropertyGroupExporterService
{
    public function assignData(PimPropertyGroup $group, Collection $options, Collection $locales): PropertyGroup
    {
        $translations = $this->getPropertyGroupConfigLabels($group, $locales);

        return new PropertyGroup(
            id: GenerateIdService::getPropertyGroupId($group),
            name: $group->description,
            description: $this->getDescription($group),
            displayType: $this->getDisplayType($group),
            sortingType: 'alphanumeric',
            filterable: $group->filterable,
            visibleOnProductDetailPage: true,
            options: $options->toArray(),
            translations: $translations,
        );
    }

    protected function getPropertyGroupConfigLabels(PimPropertyGroup $group, Collection $locales): array
    {
        $translations = [];
        $options = $group->translations->keyBy('language_id');

        $locales->each(function ($locale, $code) use ($options, &$translations) {
            $code = PimCustomFieldsExporterService::formatLanguageCode($code);
            // exclude default language (ex.: de-DE)
            if (isset($options[$locale->id])) {
                $translations[$code] = [
                    'name' => $options[$locale->id]->description,
                ];
            }
        });

        return $translations;
    }

    /**
     * @throws Exception
     */
    protected function getDisplayType(PimPropertyGroup $group): string
    {
        switch ($group->custom_fields['form']['type']) {
            case PimFormType::COLOR->name:
                return PropertyGroupType::COLOR->value;

            case PimFormType::SELECT->name:
            case PimFormType::MULTISELECT->name:
            case PimFormType::BOOL->name:
                return PropertyGroupType::SELECT->value;

            case PimFormType::TEXT->name:
            case PimFormType::TEXTAREA->name:
            case PimFormType::NUMBER->name:
            case PimFormType::PRICE->name:
            case PimFormType::URL->name:
            case PimFormType::FILEUPLOAD->name:
            case PimFormType::DATE->name:
                return PropertyGroupType::TEXT->value;

            default:
                throw new Exception('Unknown form type: '.$group->custom_fields['form']['type']);
        }
    }

    protected function getDescription(PimPropertyGroup $group): string
    {
        return 'SDH Pim: '.$group->description.' from ERP field: '.$group->name;
    }

    public function getPropertyGroups(): Collection
    {
        return PimPropertyGroup::query()
            ->with('groupOptions')
            ->with('translations')
            ->with('groupOptions.translations')
            ->whereIn('custom_fields->form->type', [
                PimFormType::SELECT->name,
                PimFormType::MULTISELECT->name,
                PimFormType::COLOR->name,
            ])
            ->get();

    }

    public function getExceptionFieldMapping(PimMappingType $mappingType, array $mapping): Collection
    {
        $names = array_keys($mapping[$mappingType->value]);

        return PimPropertyGroup::query()
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');
    }

    public function getRetailPricePropertyId(Collection $exceptionFieldMapping, array $configMapping): string
    {
        $priceKey = array_search('price.net', $configMapping[PimMappingType::PRODUCT->value]);

        return $exceptionFieldMapping[$priceKey]->id;
    }

    public function getWeightPropertyId(Collection $exceptionFieldMapping, array $configMapping): string
    {
        $weightKey = array_search('weight', $configMapping[PimMappingType::PRODUCT->value]);

        return $exceptionFieldMapping[$weightKey]->id;
    }
}
