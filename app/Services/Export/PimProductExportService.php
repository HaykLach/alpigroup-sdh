<?php

namespace App\Services\Export;

use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\PimCurrency;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use SmartDato\SdhShopwareSdk\Controllers\ProductController;
use SmartDato\SdhShopwareSdk\DataTransferObjects\PriceItem;
use SmartDato\SdhShopwareSdk\DataTransferObjects\Product;
use SmartDato\SdhShopwareSdk\DataTransferObjects\ProductVisibility;
use SmartDato\SdhShopwareSdk\Enums\ProductVisibilityType;
// @todo remove SmartDato\Shopware6 dependency
use SmartDato\Shopware6\App\Models\Shopware6Currency\Shopware6CurrencyExtension;
use SmartDato\Shopware6\App\Models\Shopware6Manufacturer\Shopware6ManufacturerExtension;
use SmartDato\Shopware6\App\Models\Shopware6Tax\Shopware6TaxExtension;

class PimProductExportService
{
    public function dispatchProductCreateJobs(
        PimProduct $pimProduct,
        string $sw6Currency,
        string $pricePropertyId,
        string $weightPropertyId,
        string $mediaFolderId,
        array $salesChannelIds,
        array $languageMap,
        Collection $shopwareManufacturersMap,
        Collection $shopwareTaxMap,
        Collection $pimTax,
        Collection $locales,
        Collection $customFieldMap,
        Collection $propertyGroupsThatDefineVariantsMap,
        Collection $propertyGroupsToApplyFilterMap,
        Collection $pimPropertyGroupsMedia,
    ): void {
        $configuratorSettings = [];
        $mainProductOptions = [];

        $productId = GenerateIdService::getProductId($pimProduct);
        $productController = new ProductController;

        $existingProduct = $productController->get($productId);

        $data = $this->assignData(
            $pimProduct,
            $existingProduct,
            $sw6Currency,
            $pricePropertyId,
            $weightPropertyId,
            $mediaFolderId,
            $salesChannelIds,
            $languageMap,
            $shopwareManufacturersMap,
            $shopwareTaxMap,
            $pimTax,
            $locales,
            $customFieldMap,
            $propertyGroupsThatDefineVariantsMap,
            $propertyGroupsToApplyFilterMap,
            $pimPropertyGroupsMedia,
            $configuratorSettings,
            $mainProductOptions,
        );

        if ((new PimProductExportDataHashService)->checkIfRequestNeeded($pimProduct, $data) || ! $existingProduct) {
            if ($existingProduct) {
                $success = $productController->update($data);
            } else {
                $success = $productController->create($data);
            }

            if ($success) {
                (new PimProductMediaExportService)->dispatchProductImagesJobs($existingProduct, $pimProduct);
            }
        }
    }

    public function assignData(
        PimProduct $pimProduct,
        Product|false $existingProduct,
        string $sw6Currency,
        string $pricePropertyId,
        string $weightPropertyId,
        string $mediaFolderId,
        array $salesChannelIds,
        array $languageMap,
        Collection $shopwareManufacturersMap,
        Collection $shopwareTaxMap,
        Collection $pimTax,
        Collection $locales,
        Collection $customFieldMap,
        Collection $propertyGroupsThatDefineVariantsMap,
        Collection $propertyGroupsToApplyFilterMap,
        Collection $pimPropertyGroupsMedia,
        array $configuratorSettingsKeyById = [],
        array $mainProductOptions = [],
    ): Product {
        $isVariant = ! $pimProduct->isMainProduct || $pimProduct->variations->isEmpty();
        $isMainProductWithoutVariants = $pimProduct->isMainProduct && $pimProduct->variations->isEmpty();
        $setConfiguratorSettings = ! $isVariant || $isMainProductWithoutVariants;
        $setOptions = $isVariant || $isMainProductWithoutVariants;
        $setChildren = $pimProduct->isMainProduct;

        $mediaEntities = PimProductMediaExportService::getProductImageMediaEntities($pimProduct, $mediaFolderId);
        $price = $this->assignProductPrice($pimProduct, $sw6Currency, $pricePropertyId, $pimTax);
        // @todo if price is null do not export, also color and colorfilter

        $productData = [
            'manufacturerId' => $shopwareManufacturersMap[$pimProduct->pim_manufacturer_id],
            'taxId' => $shopwareTaxMap[$pimProduct->pim_tax_id],
            'coverId' => PimProductMediaExportService::assignCoverImage($pimProduct),
            'price' => $price,
            'productNumber' => $pimProduct->product_number,
            'active' => $pimProduct->trashed() ? false : $pimProduct->active,
            'ean' => $pimProduct->identifier,
            'name' => $pimProduct->name,
            'description' => $this->generateHtmlFromMarkdown($pimProduct->description),
            'stock' => $pimProduct->stock,
            'createdAt' => Carbon::parse($pimProduct->created_at),
            'children' => ! empty($children) ? $children : null,
            'id' => GenerateIdService::getProductId($pimProduct),
            'media' => ! empty($mediaEntities) ? $mediaEntities : null,
            'weight' => $this->assignProductWeight($pimProduct, $weightPropertyId),
        ];

        if ($setConfiguratorSettings) {
            // collect options that define variants
            $configuratorSettingsKeyById = $this->getConfiguratorSettings($pimProduct, $isMainProductWithoutVariants, $propertyGroupsThatDefineVariantsMap);
            // add $configuratorSettings to define variant properties
            $productData['configuratorSettings'] = $this->avoidSendingDuplicateConfiguratorSettings($configuratorSettingsKeyById, $existingProduct);
            // set channel visibilities
            $productData['visibilities'] = $this->assignProductVisibilities($pimProduct, $salesChannelIds);
        }

        if ($setOptions) {
            // variant options
            $productData['options'] = $this->getVariantOptions($pimProduct, $configuratorSettingsKeyById);
        }

        if ($isMainProductWithoutVariants) {
            [$customFields, $customFieldsTranslations] = $this->collectCustomFieldData($pimProduct, $pimPropertyGroupsMedia, $customFieldMap['mainAndVariant'], $languageMap, $locales, $mediaFolderId);
        } elseif (! $isVariant) {
            [$customFields, $customFieldsTranslations] = $this->collectCustomFieldData($pimProduct, $pimPropertyGroupsMedia, $customFieldMap['main'], $languageMap, $locales, $mediaFolderId);
        } else {
            [$customFields, $customFieldsTranslations] = $this->collectCustomFieldData($pimProduct, $pimPropertyGroupsMedia, $customFieldMap['variant'], $languageMap, $locales, $mediaFolderId);
        }
        $productData['customFields'] = $customFields;
        $productData['translations'] = $this->assignTranslationsData($languageMap, $locales, $pimProduct, $customFieldsTranslations);
        $productData['properties'] = $this->assignProperties($mainProductOptions, $pimProduct, $configuratorSettingsKeyById, $isMainProductWithoutVariants, $propertyGroupsToApplyFilterMap);

        if ($setChildren) {
            $children = $this->assignDataChildren(
                $pimProduct->variations,
                $existingProduct,
                $sw6Currency,
                $pricePropertyId,
                $weightPropertyId,
                $mediaFolderId,
                $salesChannelIds,
                $languageMap,
                $shopwareManufacturersMap,
                $shopwareTaxMap,
                $pimTax,
                $locales,
                $customFieldMap,
                $propertyGroupsThatDefineVariantsMap,
                $propertyGroupsToApplyFilterMap,
                $pimPropertyGroupsMedia,
                $configuratorSettingsKeyById,
                $mainProductOptions,
            );

            $productData['children'] = $children;
        }

        // return new Product based on $productData
        return new Product(
            manufacturerId: $productData['manufacturerId'],
            taxId: $productData['taxId'],
            coverId: $productData['coverId'],
            price: $productData['price'],
            productNumber: $productData['productNumber'],
            active: $productData['active'],
            ean: $productData['ean'],
            weight: $productData['weight'],
            name: $productData['name'],
            description: $productData['description'],
            customFields: $productData['customFields'],
            stock: $productData['stock'],
            createdAt: $productData['createdAt'],
            children: $productData['children'],
            id: $productData['id'],
            media: $productData['media'],
            properties: $productData['properties'] ?? null,
            options: $productData['options'] ?? null,
            configuratorSettings: $productData['configuratorSettings'] ?? null,
            visibilities: $productData['visibilities'] ?? null,
            translations: $productData['translations'],
        );
    }

    protected function avoidSendingDuplicateConfiguratorSettings(array $configuratorSettingsKeyById, Product|false $existingProduct): ?array
    {
        $configuratorSettings = array_values($configuratorSettingsKeyById);

        // avoid sending duplicate of configuratorSettings
        if ($existingProduct !== false) {
            $existingProductConfiguratorSettingsIds = collect($existingProduct->configuratorSettings)->pluck('optionId')->sort();
            $configuratorSettingsIds = collect($configuratorSettings)->pluck('optionId')->sort()->values();
            $diff = $configuratorSettingsIds->diff($existingProductConfiguratorSettingsIds);
            if ($diff->isEmpty()) {
                $configuratorSettings = null;
            } else {
                // add new options
                $configuratorSettings = array_values(
                    $diff->map(function ($optionId) {
                        return ['optionId' => $optionId];
                    })->toArray()
                );
            }
        }

        return $configuratorSettings;
    }

    protected function assignProperties(array &$mainProductOptions, PimProduct $pimProduct, array $configuratorSettings, bool $isMainProductWithoutVariants, Collection $propertyGroupsToApplyFilterMap): array
    {
        // handle properties
        if ($pimProduct->isMainProduct) {
            $mainProductOptions = $this->getMainProductOptions($pimProduct, $configuratorSettings);
            $properties = $mainProductOptions;
            if ($isMainProductWithoutVariants) {
                $properties = array_merge($properties, $this->getFilterOptions($pimProduct, $propertyGroupsToApplyFilterMap)->toArray());
            }
        } else {
            $properties = array_merge($mainProductOptions, $this->getFilterOptions($pimProduct, $propertyGroupsToApplyFilterMap)->toArray());
        }

        return $properties;
    }

    protected function getFilterOptions(PimProduct $pimProduct, Collection $propertyGroupsToApplyFilterMap): Collection
    {
        return $pimProduct->properties
            ->filter(function ($pimProperty) use ($propertyGroupsToApplyFilterMap) {
                return isset($propertyGroupsToApplyFilterMap[$pimProperty->group_id]);
            })
            ->map(function ($pimProperty) {
                return [
                    'id' => GenerateIdService::getPropertyGroupOptionId($pimProperty->propertyGroup, $pimProperty),
                    /*
                    'name' => $pimProperty->name,
                    'groupName' => $pimProperty->propertyGroup->name,
                    */
                ];
            })
            ->values();
    }

    protected function getConfiguratorSettings(PimProduct $pimProduct, bool $isMainProductWithoutVariants, Collection $propertyGroupsThatDefineVariantsMap): array
    {
        $configuratorSettings = [];
        if ($isMainProductWithoutVariants) {
            $pimProduct->properties->each(function ($pimProperty) use (&$configuratorSettings, $propertyGroupsThatDefineVariantsMap) {
                $this->addConfiguratorSettings($configuratorSettings, $pimProperty, $propertyGroupsThatDefineVariantsMap);
            });
        } else {
            $pimProduct->variations->each(function ($pimProductVariant) use (
                &$configuratorSettings,
                $propertyGroupsThatDefineVariantsMap,
            ) {
                // sort properties by property group name
                $pimProductVariant->properties->each(function ($pimProperty) use (&$configuratorSettings, $propertyGroupsThatDefineVariantsMap) {
                    $this->addConfiguratorSettings($configuratorSettings, $pimProperty, $propertyGroupsThatDefineVariantsMap);
                });
            });
        }

        return $configuratorSettings;
    }

    protected function addConfiguratorSettings(array &$configuratorSettings, PimPropertyGroupOption $pimProperty, Collection $propertyGroupsThatDefineVariantsMap): void
    {
        if (isset($propertyGroupsThatDefineVariantsMap[$pimProperty->propertyGroup->name])) {
            $configuratorSettings[$pimProperty->id] = [
                'optionId' => GenerateIdService::getPropertyGroupOptionId($pimProperty->propertyGroup, $pimProperty),
            ];
        }
    }

    protected function assignDataChildren(
        Collection $pimVariants,
        Product|false $existingProduct,
        string $sw6Currency,
        string $pricePropertyId,
        string $weightPropertyId,
        string $mediaFolderId,
        array $salesChannelIds,
        array $languageMap,
        Collection $shopwareManufacturersMap,
        Collection $shopwareTaxMap,
        Collection $pimTax,
        Collection $locales,
        Collection $customFieldMap,
        Collection $propertyGroupsThatDefineVariantsMap,
        Collection $propertyGroupsToApplyFilterMap,
        Collection $pimPropertyGroupsMedia,
        array $configuratorSettings,
        array $mainProductOptions,
    ): ?array {
        if ($pimVariants->isEmpty()) {
            return null;
        }

        $children = [];
        $pimVariants->each(function ($pimProductVariant) use (
            $existingProduct,
            $sw6Currency,
            $pricePropertyId,
            $weightPropertyId,
            $mediaFolderId,
            $salesChannelIds,
            $languageMap,
            $shopwareManufacturersMap,
            $shopwareTaxMap,
            &$children,
            $pimTax,
            $locales,
            $customFieldMap,
            $propertyGroupsThatDefineVariantsMap,
            $propertyGroupsToApplyFilterMap,
            $pimPropertyGroupsMedia,
            $configuratorSettings,
            $mainProductOptions,
        ) {
            $children[] = $this->assignData(
                $pimProductVariant,
                $existingProduct,
                $sw6Currency,
                $pricePropertyId,
                $weightPropertyId,
                $mediaFolderId,
                $salesChannelIds,
                $languageMap,
                $shopwareManufacturersMap,
                $shopwareTaxMap,
                $pimTax,
                $locales,
                $customFieldMap,
                $propertyGroupsThatDefineVariantsMap,
                $propertyGroupsToApplyFilterMap,
                $pimPropertyGroupsMedia,
                $configuratorSettings,
                $mainProductOptions,
            );
        });

        return $children;
    }

    protected function assignTranslationsData(array $languageMap, Collection $locales, PimProduct $pimProduct, array $customFieldsTranslations): ?array
    {
        $pimTranslations = $pimProduct->translations->keyBy('language_id');

        $translations = [];
        foreach ($languageMap as $locale => $languageId) {
            $pimLanguageId = $locales->search($locale);
            if ($pimLanguageId !== false) {
                $translations[$languageId] = [
                    'name' => $pimTranslations[$pimLanguageId]->name,
                    'description' => $this->generateHtmlFromMarkdown($pimTranslations[$pimLanguageId]->description),
                    'customFields' => $customFieldsTranslations[$languageId] ?? [],
                ];
            }
        }

        return $translations;
    }

    protected function assignProductVisibilities(PimProduct $pimProduct, array $salesChannelIds): array
    {
        $visibilities = [];
        foreach ($salesChannelIds as $salesChannelId) {
            $visibilities[] = new ProductVisibility(
                id: GenerateIdService::getProductVisibilityId($pimProduct, $salesChannelId['id']),
                productId: GenerateIdService::getProductId($pimProduct),
                salesChannelId: $salesChannelId['id'],
                visibility: ProductVisibilityType::ALL->value,
            );
        }

        return $visibilities;
    }

    protected function collectCustomFieldData(
        PimProduct $pimProduct,
        Collection $pimPropertyGroupsMedia,
        Collection $customFieldMap,
        array $languageMap,
        Collection $locales,
        string $mediaFolderId,
    ): array {
        $data = [];
        $mediaData = [];
        $translations = [];
        $pimTranslations = $pimProduct->translations->keyBy('language_id');

        $customFieldMap->each(function ($customField, $propertyGroupId) use (
            $pimProduct,
            $languageMap,
            $locales,
            $pimTranslations,
            &$data,
            &$mediaData,
            &$translations,
            $pimPropertyGroupsMedia,
            $mediaFolderId,
        ) {
            $value = $pimProduct->custom_fields['properties'][$propertyGroupId] ?? null;
            if ($value !== null) {

                switch ($customField['type']) {
                    case PimFormType::BOOL->name:
                    case PimFormType::TEXT->name:
                    case PimFormType::URL->name:
                        $data[$customField['name']] = (string) $value;
                        $translatedValue = $this->collectCustomFieldTranslationsData($customField['name'], $languageMap, $locales, $pimTranslations, $propertyGroupId);
                        if ($translatedValue) {
                            $translations[] = $translatedValue;
                        }
                        break;

                    case PimFormType::TEXTAREA->name:
                        $data[$customField['name']] = $this->generateHtmlFromMarkdown($value);
                        $translatedValue = $this->collectCustomFieldTranslationsData($customField['name'], $languageMap, $locales, $pimTranslations, $propertyGroupId, [$this, 'generateHtmlFromMarkdown']);
                        if ($translatedValue) {
                            $translations[] = $translatedValue;
                        }
                        break;

                    default:
                        throw new Exception("Custom field type not supported: {$customField['type']}");
                }

            } elseif ($customField['type'] === PimFormType::FILEUPLOAD->name) {
                $mediaData = PimMediaExportService::upsertItemMedia($pimProduct, PimMappingType::PRODUCT, $locales, $pimPropertyGroupsMedia, $mediaFolderId);
            }
        });

        $translationsData = $this->restructureCustomFieldTranslationsData($translations);

        // add media custom fields
        if (isset($mediaData['customFields'])) {
            $data = array_merge($data, $mediaData['customFields']);
            foreach ($mediaData['translations'] as $locale => $mediaTranslation) {
                $locale = PimCustomFieldsExporterService::reformatLanguageCode($locale);
                $foreignLangId = $languageMap[$locale];
                $translationsData[$foreignLangId] = array_merge($translationsData[$foreignLangId] ?? [], $mediaTranslation['customFields']);
            }
        }

        return [$data, $translationsData];
    }

    protected function restructureCustomFieldTranslationsData(array $translations): array
    {
        $restructured = [];

        foreach ($translations as $collection) {
            foreach ($collection as $item) {
                $languageId = $item['languageId'];
                if (! isset($restructured[$languageId])) {
                    $restructured[$languageId] = [];
                }
                $restructured[$languageId] = array_merge($restructured[$languageId], $item['customFields']);
            }
        }

        return $restructured;
    }

    protected function collectCustomFieldTranslationsData(string $customFieldName, array $languageMap, Collection $locales, Collection $pimTranslations, string $propertyGroupId, ?callable $formatter = null): ?Collection
    {
        $translations = collect();
        foreach ($languageMap as $locale => $languageId) {
            $pimLanguageId = $locales->search($locale);
            if ($pimLanguageId !== false) {
                if (isset($pimTranslations[$pimLanguageId])
                    && isset($pimTranslations[$pimLanguageId]->custom_fields['properties'])
                    && isset($pimTranslations[$pimLanguageId]->custom_fields['properties'][$propertyGroupId])
                ) {
                    $value = (string) $pimTranslations[$pimLanguageId]->custom_fields['properties'][$propertyGroupId];
                    if ($formatter) {
                        $value = $formatter($value);
                    }
                    $translations->push([
                        'languageId' => $languageId,
                        'customFields' => [
                            $customFieldName => $value,
                        ],
                    ]);
                }
            }
        }

        return ! empty($translations) ? $translations : null;
    }

    protected function getMainProductOptions(PimProduct $pimProduct, array $configuratorSettings): array
    {
        $propertiesData = [];
        $pimProduct->properties->each(function ($pimProperty) use (&$propertiesData, $configuratorSettings) {
            $valid = ! isset($configuratorSettings[$pimProperty->id]);
            if ($valid) {
                $propertiesData[] = [
                    'id' => GenerateIdService::getPropertyGroupOptionId($pimProperty->propertyGroup, $pimProperty),
                    /*
                    'name' => $pimProperty->name,
                    'groupName' => $pimProperty->propertyGroup->name,
                    */
                ];
            }
        });

        return $propertiesData;
    }

    protected function getVariantOptions(PimProduct $pimProduct, array $configuratorSettings): array
    {
        $propertiesData = [];
        // dd($pimProduct->properties->toArray());
        $pimProduct->properties->each(function ($pimProperty) use (&$propertiesData, $configuratorSettings) {
            $valid = isset($configuratorSettings[$pimProperty->id]);
            if ($valid) {
                $propertiesData[] = [
                    'id' => GenerateIdService::getPropertyGroupOptionId($pimProperty->propertyGroup, $pimProperty),
                    // 'name' => $pimProperty->name,
                    // 'groupName' => $pimProperty->propertyGroup->name,
                ];
            }
        });

        return $propertiesData;
    }

    protected function generateHtmlFromMarkdown(?string $text = null): string
    {
        if (empty($text)) {
            return '';
        }

        // Replace line breaks with <br> tags
        $text = str_replace(["\r\n", "\n", "\r"], '<br>', $text);

        // Convert bullet points to <li> tags
        $text = preg_replace('/•\s*(.*?)(<br>|$)/', '<li>$1</li>', $text);

        // Wrap the <li> tags with <ul> tags
        $text = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text);

        return $text;
    }

    protected function assignProductWeight(PimProduct $pimProduct, string $weightPropertyId): ?float
    {
        $weight = $pimProduct->custom_fields['properties'][$weightPropertyId] ?? null;
        if ($weight === null) {
            return null;
        }

        return (float) $weight / 1000;
    }

    protected function assignProductPrice(PimProduct $pimProduct, string $sw6Currency, string $pricePropertyId, Collection $pimTax): ?array
    {
        $price = $pimProduct->prices[$pricePropertyId];
        if (empty($pimProduct->prices[$pricePropertyId])) {
            return null;
        }

        return [
            new PriceItem(
                currencyId: $sw6Currency,
                gross: $this->calculateProductGrossPrice($price, $pimTax->keyBy('id')[$pimProduct->pim_tax_id]->tax_rate),
                net: $pimProduct->prices[$pricePropertyId],
                linked: true,
            ),
        ];
    }

    protected function calculateProductGrossPrice(float $price, float $pimTaxRate): float
    {
        return $price * (1 + $pimTaxRate / 100);
    }

    public function getShopwareCurrency(): string
    {
        return Shopware6CurrencyExtension::select('shopware_currency_id')
            ->where('pim_currency_id', PimCurrency::first()->id)
            ->pluck('shopware_currency_id')
            ->first();
    }

    public function getProductShopwareManufacturersMap(): Collection
    {
        return Shopware6ManufacturerExtension::all()
            ->pluck('shopware_manufacturer_id', 'pim_manufacturer_id');
    }

    public function getProductShopwareTaxMap(): Collection
    {
        return Shopware6TaxExtension::all()
            ->pluck('shopware_tax_id', 'pim_tax_id');
    }
}
