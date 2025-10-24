<?php

namespace App\Services\Export;

use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Property\PimPropertyGroup;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use SmartDato\SdhShopwareSdk\DataTransferObjects\CustomFieldSet;
use SmartDato\SdhShopwareSdk\Enums\CustomFieldConfigComponentName;
use SmartDato\SdhShopwareSdk\Enums\CustomFieldConfigType;
use SmartDato\SdhShopwareSdk\Enums\CustomFieldType;

class PimCustomFieldsExporterService
{
    public function queryProductCustomPropertyGroups(PimMappingType $pimMappingType, array $configMapping): Builder
    {
        $query = PimPropertyGroup::query()
            ->with('groupOptions')
            ->with('translations')
            ->with('groupOptions.translations')
            ->where('custom_fields->type', '=', $pimMappingType->name)
            ->whereNotIn('custom_fields->form->type', [PimFormType::SELECT->name, PimFormType::MULTISELECT->name, PimFormType::COLOR->name]);

        if (isset($configMapping[$pimMappingType->value])) {
            $query = $query->whereNotIn('name', array_keys($configMapping[$pimMappingType->value]));
        }

        return $query;
    }

    public function getCustomFieldMap(PimMappingType $pimMappingType, array $configMapping): Collection
    {
        $customFieldMap = collect();
        $pimPropertyGroups = $this->queryProductCustomPropertyGroups($pimMappingType, $configMapping)->get();

        $main = collect();
        $variant = collect();
        $mainAndVariant = collect();

        $pimPropertyGroups->each(function ($pimPropertyGroup) use (&$main, &$variant, &$mainAndVariant, $pimMappingType) {
            $item = [
                'name' => $this->getCustomFieldName($pimMappingType, $pimPropertyGroup),
                'type' => $pimPropertyGroup->custom_fields['form']['type'],
            ];

            if ($pimPropertyGroup->custom_fields['edit']['main']) {
                $main->put($pimPropertyGroup->id, $item);
                $mainAndVariant->put($pimPropertyGroup->id, $item);
            }

            if ($pimPropertyGroup->custom_fields['edit']['variant']) {
                $variant->put($pimPropertyGroup->id, $item);
                $mainAndVariant->put($pimPropertyGroup->id, $item);
            }
        });

        $customFieldMap->put('main', $main);
        $customFieldMap->put('variant', $variant);
        $customFieldMap->put('mainAndVariant', $mainAndVariant);

        return $customFieldMap;
    }

    public function getCustomFieldSet(PimMappingType $mappingType, Collection $locales, array $configMapping): CustomFieldSet
    {
        $name = $this->getCustomFieldSetName($mappingType);
        $customFieldSetId = GenerateIdService::getCustomFieldSetId($name);

        return new CustomFieldSet(
            id: $customFieldSetId,
            name: $name,
            customFields: $this->getCustomFields($mappingType, $locales, $customFieldSetId, $configMapping),
            config: [
                'label' => $this->getCustomFieldSetConfigLabels($mappingType, $locales),
                'translated' => true,
            ],
            active: true,
            global: false,
            position: 1,
            relations: [
                [
                    'id' => GenerateIdService::getCustomFieldSetRelationId($name),
                    'customFieldSetId' => $customFieldSetId,
                    'entityName' => $this->getCustomFieldSetRelationEntity($mappingType),
                ],
            ],
        );
    }

    protected function getCustomFieldSetRelationEntity(PimMappingType $mappingType): string
    {
        switch ($mappingType) {
            case PimMappingType::MANUFACTURER:
                return 'product_manufacturer';
            default:
                return 'product';
        }
    }

    protected function getCustomFieldSetName(PimMappingType $mappingType): string
    {
        return 'sdh_pim__custom_fields_'.strtolower($mappingType->name);
    }

    public function getCustomFieldName(PimMappingType $mappingType, PimPropertyGroup $group): string
    {
        return $this->getCustomFieldSetName($mappingType).'__'.$this->getName($group->name);
    }

    protected function getCustomFields(PimMappingType $mappingType, Collection $locales, string $customFieldSetId, array $configMapping): array
    {
        $customFields = collect();
        $localEntries = $this->queryProductCustomPropertyGroups($mappingType, $configMapping)->get();
        $localEntries->each(function ($group, $index) use ($customFields, $mappingType, $locales, $customFieldSetId) {
            $pos = $index + 1;
            $customField = $this->assignCustomFieldData($group, $mappingType, $pos, $customFieldSetId, $locales);
            $customFields->push($customField);
        });

        return $customFields->toArray();
    }

    protected function assignCustomFieldData(PimPropertyGroup $group, PimMappingType $mappingType, int $pos, string $customFieldSetId, Collection $locales): array
    {
        $labels = $this->getCustomFieldConfigLabels($locales, $group);

        $config = [
            'componentName' => $this->getCustomFieldConfigComponentName($group),
            'customFieldPosition' => $pos,
            'customFieldType' => $this->getCustomFieldConfigType($group),
            'label' => $labels,
        ];
        $this->addCustomFieldDataConfigSubType($config, $group);

        return [
            'id' => GenerateIdService::getCustomFieldId($group),
            'name' => $this->getCustomFieldName($mappingType, $group),
            'type' => $this->getCustomFieldType($group),
            'config' => $config,
            'active' => true,
            'customFieldSetId' => $customFieldSetId,
            'allowCustomerWrite' => false,
            'allowCartExpose' => false,
        ];
    }

    protected function getCustomFieldSetConfigLabels(PimMappingType $mappingType, Collection $locales): array
    {
        return array_merge(...$locales->map(function ($language) use ($mappingType) {
            return [
                $this->formatLanguageCode($language->local->code) => 'SDH Pim custom fields for: '.$mappingType->value,
            ];
        })->toArray());
    }

    protected function getCustomFieldConfigLabels(Collection $locales, PimPropertyGroup $group): array
    {
        return array_merge(...$locales->map(function ($language) use ($group) {
            $description = $group->translations->where('language_id', $language->id)->first()->description ?? $group->description;

            return [
                $this->formatLanguageCode($language->local->code) => $description,
            ];
        })->toArray());
    }

    public static function formatLanguageCode(string $code): string
    {
        return str_replace('_', '-', $code);
    }

    public static function reformatLanguageCode(string $code): string
    {
        return str_replace('-', '_', $code);
    }

    protected function addCustomFieldDataConfigSubType(array &$config, PimPropertyGroup $group): void
    {
        switch ($group->custom_fields['form']['type']) {
            case PimFormType::NUMBER->name:
                $config['numberType'] = CustomFieldType::FLOAT->value;
                break;
        }
    }

    protected function getName(string $name): string
    {
        $name = strtolower($name);

        // List of TWIG special characters to replace
        $twigSpecialChars = ['{{', '}}', '{%', '%}', '{#', '#}', '-', '#'];

        // Replace each special character with an underscore
        foreach ($twigSpecialChars as $char) {
            $name = str_replace($char, '_', $name);
        }

        return $name;
    }

    protected function getCustomFieldConfigComponentName(PimPropertyGroup $group): string
    {
        return match ($group->custom_fields['form']['type']) {
            PimFormType::FILEUPLOAD->name => CustomFieldConfigComponentName::SW_MEDIA_FIELD->value,
            PimFormType::TEXTAREA->name => CustomFieldConfigComponentName::SW_TEXT_EDITOR_FIELD->value,
            default => CustomFieldConfigComponentName::SW_FIELD->value,
        };
    }

    protected function getCustomFieldConfigType(PimPropertyGroup $group): string
    {
        switch ($group->custom_fields['form']['type']) {
            case PimFormType::URL->name:
            case PimFormType::TEXT->name:
                return CustomFieldConfigType::TEXT->value;

            case PimFormType::TEXTAREA->name:
                return CustomFieldConfigType::TEXT_EDITOR->value;

            case PimFormType::FILEUPLOAD->name:
                return CustomFieldConfigType::MEDIA->value;

            case PimFormType::BOOL->name:
                return CustomFieldConfigType::SWITCH->value;

            case PimFormType::NUMBER->name:
                return CustomFieldConfigType::NUMBER->value;

            case PimFormType::DATE->name:
                return CustomFieldConfigType::DATE->value;

            default:
                throw new Exception('Unknown form type: '.$group->custom_fields['form']['type']);
        }
    }

    /**
     * @throws Exception
     */
    protected function getCustomFieldType(PimPropertyGroup $group): string
    {
        switch ($group->custom_fields['form']['type']) {
            case PimFormType::FILEUPLOAD->name:
            case PimFormType::URL->name:
            case PimFormType::TEXT->name:
                return CustomFieldType::TEXT->value;

            case PimFormType::BOOL->name:
                return CustomFieldType::BOOL->value;

            case PimFormType::NUMBER->name:
                // return CustomFieldType::FLOAT->value;
                return CustomFieldType::INTEGER->value;

            case PimFormType::TEXTAREA->name:
                return CustomFieldType::HTML->value;

            case PimFormType::DATE->name:
                return CustomFieldType::DATE->value;

            default:
                throw new Exception('Unknown form type: '.$group->custom_fields['form']['type']);
        }
    }

    public function compareCustomFieldSetsGetOrphaned(CustomFieldSet $customFieldSet, CustomFieldSet $customFieldSetShopware): Collection
    {
        $shopwareCustomFields = collect($customFieldSetShopware->customFields);
        $localCustomFields = collect($customFieldSet->customFields);

        $shopwareNames = $shopwareCustomFields->pluck('name');
        $localNames = $localCustomFields->pluck('name');

        $diffNames = $shopwareNames->diff($localNames);

        return $shopwareCustomFields->whereIn('name', $diffNames);
    }
}
