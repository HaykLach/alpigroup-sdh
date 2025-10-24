<?php

namespace App\Services\Pim\Import;

use App\Enums\Pim\PimFormSection;
use App\Enums\Pim\PimFormStoreField;
use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOptionTranslation;
use App\Services\Pim\PimGenerateIdService;
use App\Services\Pim\PimTranslationService;
use App\Services\Pim\PropertyGroup\Form\PimColor;
use App\Settings\GeneralSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PimPropertyGroupSetupService
{
    public static function skipEntry(array $mapping): bool
    {
        $fields = [
            PimFormStoreField::CUSTOM_FIELDS->value,
            PimFormStoreField::PRICES->value,
            PimFormStoreField::MEDIA->value,
        ];

        // skip images, name and description
        if ($mapping['type'] === 'image' || ! in_array($mapping['field'], $fields)) {
            return true;
        }

        return false;
    }

    public static function getOptionsFromTableCol(string $fieldname): Collection
    {
        return DB::table('vendor_catalog_entries')
            ->selectRaw('DISTINCT JSON_UNQUOTE(JSON_EXTRACT(data, ?)) as '.DB::getPdo()->quote($fieldname), ['$.'.$fieldname])
            ->pluck($fieldname)
            ->sort()
            ->values();
    }

    public static function clearOptionsNullValues(Collection $options): Collection
    {
        return $options->filter(function ($value) {
            return $value !== 'null';
        });
    }

    public static function addPropertyGroupsAndOptions(array $mapping, PimMappingType $mappingType, Collection $otherLanguages): void
    {
        if (! empty($mapping[$mappingType->value] ?? null)) {
            foreach ($mapping[$mappingType->value] as $fieldname => $value) {

                // skip images, name and description
                if (PimPropertyGroupSetupService::skipEntry($value)) {
                    continue;
                }

                $edit = [];
                if ($mappingType === PimMappingType::PRODUCT) {
                    $edit = [
                        'edit' => [
                            'main' => $value['edit']['main'],
                            'variant' => $value['edit']['variant'],
                        ],
                    ];
                }

                /* @var $section PimFormSection */
                $section = $value['section'];
                $translatable = $value['translatable'] ?? false;
                $required = $value['required'] ?? false;
                $readonly = $value['readonly'] ?? false;

                $formType = $value['type'];
                if (in_array($formType, [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name])) {
                    $filterable = 1;
                } else {
                    $filterable = isset($value['filterable']) && $value['filterable'] ? 1 : 0;
                }

                $formConfigFormat = [];
                if (isset($value['format'])) {
                    $formConfigFormat['config']['format'] = $value['format'];
                }

                $validationAccepted = [];
                if (isset($value['acceptedFileTypes'])) {
                    $validationAccepted['acceptedFileTypes'] = $value['acceptedFileTypes'];
                }

                $collection = [];
                if ($formType === PimFormType::FILEUPLOAD->name) {
                    $collection['collection'] = $value['collection'];
                }

                $propertyGroupId = PimGenerateIdService::getPropertyGroupId($fieldname);

                $entry = [
                    'name' => $fieldname,
                    'description' => $value['label'],
                    'filterable' => $filterable,
                    'custom_fields' => [
                        'type' => $mappingType->name,
                        'translatable' => $translatable,
                        'section' => $section,
                        ...$edit,
                        'form' => [
                            'type' => $formType,
                            ...$formConfigFormat,
                            'validation' => [
                                'required' => $required,
                                ...$validationAccepted,
                            ],
                            'readonly' => $readonly,
                        ],
                        ...$collection,
                        'field' => $value['field'],
                    ],
                ];

                switch ($formType) {
                    case PimFormType::MULTISELECT->name:
                    case PimFormType::SELECT->name:
                    case PimFormType::COLOR->name:
                        // select distinct json field "data" look for "XF_Cut"
                        $options = PimPropertyGroupSetupService::getOptionsFromTableCol($entry['name']);

                        // remove null value from $options
                        $options = PimPropertyGroupSetupService::clearOptionsNullValues($options);

                        // add PropertyGroup
                        $propertyGroup = PimPropertyGroupSetupService::upsertPropertyGroup($entry, $propertyGroupId);

                        PimPropertyGroupSetupService::createIfNotExistsPropertyGroupOption($formType, $options, $propertyGroup, $otherLanguages);

                        break;

                    default:
                        PimPropertyGroupSetupService::upsertPropertyGroup($entry, $propertyGroupId);
                }
            }
        }
    }

    protected static function upsertPropertyGroup(array $entry, string $propertyGroupId): PimPropertyGroup
    {
        // upsert PropertyGroup that contains id
        return PimPropertyGroup::updateOrCreate(['id' => $propertyGroupId], $entry);
    }

    protected static function createIfNotExistsPropertyGroupOption(string $formType, Collection $options, PimPropertyGroup $propertyGroup, Collection $otherLanguages): void
    {
        // add PropertyGroupOptions
        $options->map(function ($name, $index) use ($propertyGroup, $otherLanguages, $formType) {

            $position = $index + 1;

            $propertyGroupOptionId = PimGenerateIdService::getPropertyGroupOptionId($name, $propertyGroup->id);
            $optionData = [
                'id' => $propertyGroupOptionId,
                'name' => $name,
                'position' => $position,
                'group_id' => $propertyGroup->id,
            ];

            $option = PimPropertyGroupOption::updateOrCreate(['id' => $optionData['id']], $optionData);

            if ($formType === PimFormType::COLOR->name && empty($option->custom_fields[PimColor::CUSTOM_FIELD_KEY])) {
                $optionData['custom_fields'] = [
                    PimColor::CUSTOM_FIELD_KEY => PimColor::FALLBACK_COLOR,
                ];
            }

            if ($propertyGroup[PimFormStoreField::CUSTOM_FIELDS->value]['translatable'] === true) {

                PimPropertyGroupOption::updateOrCreate(['id' => $optionData['id']], $optionData);

                $otherLanguages->each(function ($langCode, $languageId) use ($position, $name, $option) {

                    $optionData = [
                        'language_id' => $languageId,
                        'name' => $name,
                        'position' => $position,
                        'property_group_option_id' => $option->id,
                    ];

                    PimPropertyGroupOptionTranslation::updateOrCreate([
                        'property_group_option_id' => $optionData['property_group_option_id'],
                        'language_id' => $optionData['language_id'],
                    ], $optionData);
                });
            }
        });
    }

    public static function handlePropertyGroupsAndOptions(array $vendorMappingConfig): void
    {
        $translationService = new PimTranslationService;
        $otherLanguages = $translationService->getExtraLanguages();
        $defaultLangCode = $translationService->getDefaultLanguageCodeShort();

        // add PropertyGroups, custom_fields and prices
        PimPropertyGroupSetupService::addPropertyGroupsAndOptions($vendorMappingConfig, PimMappingType::PRODUCT, $otherLanguages);
        PimPropertyGroupSetupService::addPropertyGroupsAndOptions($vendorMappingConfig, PimMappingType::MANUFACTURER, $otherLanguages);

        $settings = new GeneralSettings;
        if ($settings->translationService_enabled && ($settings->autoTranslateByRemoteService || $settings->autoAssignColorByRemoteService)) {

            if ($settings->autoTranslateByRemoteService) {
                $extraLangIdCodes = $translationService->getExtraLanguagesShort();
                $translationService->translateAllPropertyGroups($defaultLangCode, $extraLangIdCodes);
                $translationService->translateAllPropertyGroupOptions($defaultLangCode, $extraLangIdCodes);
            }

            if ($settings->autoAssignColorByRemoteService) {
                $translationService->assignColorAllPropertyGroupOptions($defaultLangCode);
            }
        }
    }
}
