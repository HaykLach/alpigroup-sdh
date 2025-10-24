<?php

namespace App\Services\Pim\Import;

use App\Enums\Pim\PimFormStoreField;
use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Enums\VendorCatalog\VendorCatalogImportEntryState;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Models\VendorCatalog\VendorCatalogEntry;
use App\Services\Pim\PimProductTranslationService;
use App\Services\Pim\PimPropertyGroupService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PimProductImportService
{
    protected static function getMainEntriesToImport(array $queryModifier): Collection
    {
        $groupBy = self::replaceConfigPointer($queryModifier['mainProductIdentifier']);

        return VendorCatalogEntry::query()
            ->select($groupBy.' as main')
            ->where('state', VendorCatalogImportEntryState::NEW)
            ->groupBy($groupBy)
            ->pluck('main');
    }

    protected static function replaceConfigPointer(string $pointer): string
    {
        return str_replace('.', '->', $pointer);
    }

    public static function handleImport(array $vendorConfig, array $manufacturers, Collection $otherLanguages): array
    {
        $mapping = $vendorConfig['mapping'][PimMappingType::PRODUCT->value];
        $queryModifier = $vendorConfig['queryModifier'][PimMappingType::PRODUCT->value];

        $updateableProperties = PimPropertyGroupService::determineUpdateableProperties($mapping);
        $pimPropertyGroups = PimPropertyGroupService::getGroups(PimMappingType::PRODUCT)->keyBy('id');

        $tax = PimTax::query()
            ->pluck('id', 'tax_rate');

        [$modelFields, $customFields] = self::distinguishFields($mapping);
        [$nameGroupId, $nameOptionId] = self::getGroupsWithOptions();

        $handledRecords = collect();
        $handledMainRecords = collect();

        $mainEntryIds = self::getMainEntriesToImport($queryModifier);
        $mainEntryIds->map(function ($productNumber) use (
            $handledRecords,
            $handledMainRecords,
            $queryModifier,
            $updateableProperties,
            $otherLanguages,
            $mapping,
            $manufacturers,
            $modelFields,
            $customFields,
            $nameGroupId,
            $nameOptionId,
            $tax,
            $pimPropertyGroups
        ) {
            $group = VendorCatalogEntry::query()
                ->where('state', VendorCatalogImportEntryState::NEW)
                ->where(self::replaceConfigPointer($queryModifier['mainProductIdentifier']), $productNumber)
                ->orderBy(self::replaceConfigPointer($queryModifier['sortBy']))
                ->get();

            $parentId = null;
            $hasVariants = $group->count() > 1;

            foreach ($group as $record) {

                $modelValues = self::assignFieldData($otherLanguages, $modelFields, $record, $nameGroupId, $tax, $manufacturers);
                [$modelCustomFields, $mainProductModelCustomFields] = self::assignCustomFieldData($customFields, $record, $nameGroupId, $mapping);
                [$productProperties, $mainProductProperties] = self::assignPropertiesData($record, $customFields, $nameGroupId, $nameOptionId, $mapping);

                // generate main product if product has no parent
                $identifier = $hasVariants ? null : $modelValues['identifier'];
                $mainProductModelCustomFields = $hasVariants ? $mainProductModelCustomFields : $modelCustomFields;
                $mainProductProperties = $hasVariants ? $mainProductProperties : $productProperties;

                $mainProduct = self::upsertMainProduct($parentId, $productNumber, $identifier, $modelValues, $mainProductModelCustomFields, $otherLanguages, $mainProductProperties, $pimPropertyGroups, $updateableProperties);
                if ($mainProduct !== null) {
                    $handledMainRecords->push($mainProduct->id);
                    $parentId = $mainProduct->id;
                }

                // add variation if group has more than 1 entry
                if ($hasVariants) {
                    $product = self::upsertProduct($parentId, $modelValues, $modelCustomFields, $otherLanguages, $productProperties, $pimPropertyGroups, $updateableProperties);
                    $handledRecords->push($product->id);
                }

                // update record state and save
                $record->update(['state' => VendorCatalogImportEntryState::PROCESSED]);
            }
        });

        return [
            'variant' => $handledRecords,
            'main' => $handledMainRecords,
        ];
    }

    public static function deleteUnhandledProducts(array $handledRecords): int
    {
        $handledRecords = $handledRecords['variant']->merge($handledRecords['main']);

        $count = PimProduct::query()
            ->whereNotIn('id', $handledRecords)
            ->count();

        PimProduct::query()
            ->whereNotIn('id', $handledRecords)
            ->delete();

        return $count;
    }

    protected static function upsertProduct($parentId, array $modelValues, array $modelCustomFields, Collection $otherLanguages, array $productProperties, Collection $pimPropertyGroups, ?Collection $updateableProperties = null): PimProduct
    {
        // add record to pim_products
        $data = [
            'parent_id' => $parentId,
            ...$modelValues,
            ...$modelCustomFields,
        ];
        unset($data['translations']);
        unset($data[PimFormStoreField::MAIN_NAME->value]);

        // overwrite variant name, use main name
        $data['name'] = $modelValues[PimFormStoreField::MAIN_NAME->value];

        return self::upsert($data, $modelValues['translations'], $otherLanguages, $productProperties, $pimPropertyGroups, $updateableProperties);
    }

    protected static function upsertMainProduct(?string $parentId, string $productNumber, ?string $identifier, array $modelValues, array $modelCustomFields, Collection $otherLanguages, array $mainProductProperties, Collection $pimPropertyGroups, ?Collection $updateableProperties = null): ?PimProduct
    {
        $product = null;

        // add main product
        if ($parentId === null) {

            $prices = [];
            if (isset($modelValues[PimFormStoreField::PRICES->value])) {
                $prices = [
                    'prices' => $modelValues[PimFormStoreField::PRICES->value],
                ];
            }

            $data = [
                'name' => $modelValues[PimFormStoreField::MAIN_NAME->value],
                'description' => $modelValues[PimFormStoreField::DESCRIPTION->value],
                'images' => $modelValues[PimFormStoreField::IMAGES->value] ?? [],
                'identifier' => $identifier,
                'product_number' => $productNumber,
                'pim_manufacturer_id' => $modelValues['pim_manufacturer_id'] ?? null,
                'stock' => $modelValues['stock'] ?? null,
                'active' => $modelValues['active'] ?? null,
                ...$modelCustomFields,
                ...[
                    ...$prices,
                    'pim_tax_id' => $modelValues['pim_tax_id'] ?? null,
                ],
            ];

            $product = self::upsert($data, $modelValues['translations'], $otherLanguages, $mainProductProperties, $pimPropertyGroups, $updateableProperties);
        }

        return $product;
    }

    protected static function assignPropertiesData($record, array $customFields, Collection $nameGroupId, array $nameOptionId, array $mapping): array
    {
        $productProperties = [];
        $mainProductProperties = [];

        foreach ($customFields as $name => $type) {

            switch ($type) {
                case PimFormType::MULTISELECT->name:
                case PimFormType::COLOR->name:
                case PimFormType::SELECT->name:
                    $groupId = $nameGroupId[$name];
                    $optionId = $record->data[$name];
                    if (isset($nameOptionId[$groupId][$optionId])) {
                        $productProperties[] = [
                            'option_id' => $nameOptionId[$groupId][$optionId],
                        ];
                        if ($mapping[$name]['edit']['main']) {
                            $mainProductProperties[] = [
                                'option_id' => $nameOptionId[$groupId][$optionId],
                            ];
                        }
                    }
                    break;
            }
        }

        return [$productProperties, $mainProductProperties];
    }

    protected static function assignCustomFieldData(array $customFields, VendorCatalogEntry $record, $nameGroupId, array $mapping): array
    {
        $modelCustomFields = [];
        $modelCustomFieldsMain = [];

        foreach ($customFields as $name => $type) {

            switch ($type) {
                case PimFormType::URL->name:
                    $data = $record->data[$name] ?? null;
                    if ($mapping[$name]['edit']['main']) {
                        $modelCustomFieldsMain[PimFormStoreField::CUSTOM_FIELDS->value]['properties'][$nameGroupId[$name]] = self::utilityReplaceHttpHttps($data);
                    }
                    $modelCustomFields[PimFormStoreField::CUSTOM_FIELDS->value]['properties'][$nameGroupId[$name]] = self::utilityReplaceHttpHttps($data);

                    break;

                case PimFormType::DATE->name:
                    $data = $record->data[$name] ? Carbon::parse($record->data[$name]) : null;
                    if ($mapping[$name]['edit']['main']) {
                        $modelCustomFieldsMain[PimFormStoreField::CUSTOM_FIELDS->value]['properties'][$nameGroupId[$name]] = $data;
                    }
                    $modelCustomFields[PimFormStoreField::CUSTOM_FIELDS->value]['properties'][$nameGroupId[$name]] = $data;

                    break;

                case PimFormType::BOOL->name:
                case PimFormType::NUMBER->name:
                case PimFormType::TEXTAREA->name:
                case PimFormType::TEXT->name:
                    $data = $record->data[$name] ?? null;
                    if ($mapping[$name]['edit']['main']) {
                        $modelCustomFieldsMain[PimFormStoreField::CUSTOM_FIELDS->value]['properties'][$nameGroupId[$name]] = $data;
                    }
                    $modelCustomFields[PimFormStoreField::CUSTOM_FIELDS->value]['properties'][$nameGroupId[$name]] = $data;

                    break;
            }
        }

        return [$modelCustomFields, $modelCustomFieldsMain];
    }

    protected static function assignFieldData(Collection $otherLanguages, array $modelFields, VendorCatalogEntry $record, $nameGroupId, $tax, $manufacturers): array
    {
        $data = [
            'translations' => [],
        ];

        $otherLanguages = $otherLanguages->flip();

        foreach ($modelFields as $erpField => $pim) {

            switch ($pim['type']) {
                case PimFormType::TEXTAREA->name:
                case PimFormType::TEXT->name:
                    // @todo use iconv to ISO-8859-1
                    $data[$pim['field']] = $pim['value'] ?? $record->data[$erpField];
                    // add translation
                    if (isset($pim['translatable']) && $pim['translatable']
                        && isset($pim['translations']) && count($pim['translations']) > 0
                    ) {
                        foreach ($pim['translations'] as $langCode => $field) {
                            $langId = $otherLanguages[$langCode];
                            $data['translations'][$langId][$pim['field']] = $record->data[$field];
                        }
                    }
                    break;

                case PimFormType::NUMBER->name:
                    $data[$pim['field']] = $pim['value'] ?? (int) $record->data[$erpField];
                    break;

                case PimFormType::BOOL->name:
                    $data[$pim['field']] = $record->data[$erpField] === true ? 1 : 0;
                    break;

                case PimFormType::PRICE->name:
                    $data[$pim['field']][$nameGroupId[$erpField]] = $record->data[$erpField];
                    break;

                case 'image':
                    if (isset($record->data[$erpField])) {
                        $data[$pim['field']][] = self::utilityReplaceHttpHttps($record->data[$erpField]);
                    }
                    break;

                case PimProductManufacturer::class:
                    $data[$pim['field']] = $manufacturers[$record->data[$erpField]];
                    break;

                case PimTax::class:
                    $data[$pim['field']] = $tax[$record->data[$erpField]] ?? null;
                    break;
            }
        }

        // fallback to variant name
        if (! isset($data[PimFormStoreField::MAIN_NAME->value])) {
            $data[PimFormStoreField::MAIN_NAME->value] = $data[PimFormStoreField::NAME->value];
        }

        return $data;

    }

    protected static function distinguishFields(array $mapping): array
    {
        $modelFields = [];
        $customFields = [];

        foreach ($mapping as $fieldname => $value) {
            if ($value['field'] === PimFormStoreField::CUSTOM_FIELDS->value) {
                $customFields[$fieldname] = $value['type'];
            } elseif ($value['field'] !== null) {
                $modelFields[$fieldname] = $value;
            }
        }

        return [$modelFields, $customFields];
    }

    protected static function getGroupsWithOptions(): array
    {
        // get PimPropertyGroups with options
        $nameGroupId = PimPropertyGroupService::getGroups(PimMappingType::PRODUCT)
            ->pluck('id', 'name');

        $nameOptionId = [];
        PimPropertyGroupOption::all()
            ->each(function ($option) use (&$nameOptionId) {
                $nameOptionId[$option->group_id][$option->name] = $option->id;
            });

        return [
            $nameGroupId,
            $nameOptionId,
        ];
    }

    protected static function upsert(array $data, array $translations, Collection $otherLanguages, array $properties, Collection $pimPropertyGroups, ?Collection $updateableProperties = null): PimProduct
    {
        /** @var PimProduct $product */
        $product = PimProduct::withTrashed()
            ->where('product_number', $data['product_number'])
            ->where('identifier', $data['identifier'])
            ->first();

        if ($product === null) {
            $product = self::create($data, $translations, $properties, $otherLanguages);
        } else {
            // if product is softdeleted, restore it
            if ($product->trashed()) {
                $product->restore();
            }

            self::update($product, $data, $translations, $properties, $pimPropertyGroups, $updateableProperties);
        }

        return $product;
    }

    protected static function create(array $data, array $translations, array $properties, Collection $otherLanguages): PimProduct
    {
        $product = PimProduct::create($data);
        $product->properties()->attach($properties);

        PimProductTranslationService::addInitialTranslations($product, $otherLanguages);
        PimProductTranslationService::updateTranslations($product, $translations);

        return $product;
    }

    protected static function update(PimProduct $product, array $data, array $translations, array $newProperties, Collection $pimPropertyGroups, ?Collection $updateableProperties = null): void
    {
        $product->update($data);
        PimProductTranslationService::updateTranslations($product, $translations);

        if ($updateableProperties && $updateableProperties->count()) {

            // check updateable properties
            $newPropertiesToHandle = array_filter($newProperties, function ($newProperty) use ($updateableProperties) {
                return isset($updateableProperties[$newProperty['option_id']]);
            });

            if (! empty($newPropertiesToHandle)) {

                $currentProperties = $product->properties->pluck('id')->toArray();
                $newProperties = array_column($newPropertiesToHandle, 'option_id');

                $areSimilar = empty(array_diff($currentProperties, $newProperties)) && empty(array_diff($newProperties, $currentProperties));

                if (! $areSimilar) {

                    $newGroupOptions = [];
                    foreach ($newPropertiesToHandle as $property) {
                        $groupId = $updateableProperties[$property['option_id']]->group_id;
                        $newGroupOptions[$groupId][] = ['option_id' => $property['option_id']];
                    }

                    // detect if $product->properties contains all options of $newGroupOptions[$groupId][], otherwise detach options by groupId
                    foreach ($newGroupOptions as $groupId => $options) {
                        $optionsOfGroup = $pimPropertyGroups->get($groupId)->groupOptions->pluck('id');

                        // update options from $product->properties
                        $product->properties()->detach($optionsOfGroup);
                        $product->properties()->attach($options);
                    }
                }
            }
        }
    }

    protected static function utilityReplaceHttpHttps(?string $url): ?string
    {
        return $url !== null ? str_replace('http://', 'https://', $url) : null;
    }
}
