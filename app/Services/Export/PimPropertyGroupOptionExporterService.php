<?php

namespace App\Services\Export;

use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Services\Pim\PropertyGroup\Form\PimColor;
use Illuminate\Support\Collection;
use SmartDato\SdhShopwareSdk\DataTransferObjects\PropertyGroupOption;

class PimPropertyGroupOptionExporterService
{
    public function assignData(
        PimPropertyGroup $group,
        PimPropertyGroupOption $option,
        Collection $locales,
    ): PropertyGroupOption {
        $translations = $this->getPropertyGroupOptionsConfigLabels($locales, $option);

        return new PropertyGroupOption(
            id: GenerateIdService::getPropertyGroupOptionId($group, $option),
            groupId: GenerateIdService::getPropertyGroupId($group),
            name: $option->name,
            position: $option->position,
            colorHexCode: $option->custom_fields[PimColor::CUSTOM_FIELD_KEY] ?? null,
            mediaId: null,
            translations: $translations,
        );
    }

    protected function getPropertyGroupOptionsConfigLabels(Collection $locales, PimPropertyGroupOption $option): array
    {
        $translations = [];
        $options = $option->translations->keyBy('language_id');

        $locales->each(function ($locale, $code) use ($options, &$translations) {
            $code = PimCustomFieldsExporterService::formatLanguageCode($code);
            // exclude default language (ex.: de-DE)
            if (isset($options[$locale->id])) {
                $translations[$code] = [
                    'name' => $options[$locale->id]->name,
                    'position' => $options[$locale->id]->position,
                ];
            }
        });

        return $translations;
    }

    public function getOptionsThatDefineVariations(array $names): Collection
    {
        return PimPropertyGroup::query()
            ->whereIn('name', $names)
            ->get()
            ->pluck('id', 'name');
    }

    public function getPropertiesToApplyFilter(array $names): Collection
    {
        return PimPropertyGroup::query()
            ->whereIn('name', $names)
            ->get()
            ->pluck('name', 'id');
    }
}
