<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimCustomerType;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class PimGenerateIdService
{
    public static function getVendorCatalogVendorId(string $name): string
    {
        return PimGenerateIdService::getUuid5(str($name)->lower()->ascii()->value(), 'vendor_catalog_vendor');
    }

    public static function getVendorCatalogImportDefinitionId(string $name)
    {
        return PimGenerateIdService::getUuid5(PimGenerateIdService::sanitizeName($name), 'vendor_catalog_import_definition');
    }

    public static function getTaxId(float $rate): string
    {
        return PimGenerateIdService::getUuid5($rate, 'tax');
    }

    public static function getCurrencyId(string $isoCode): string
    {
        return PimGenerateIdService::getUuid5(PimGenerateIdService::sanitizeName($isoCode), 'currency');
    }

    public static function getLanguageId(string $languageName): string
    {
        return PimGenerateIdService::getUuid5(PimGenerateIdService::sanitizeName($languageName), 'language');
    }

    public static function getLocaleId(string $locale): string
    {
        return PimGenerateIdService::getUuid5(PimGenerateIdService::sanitizeName($locale), 'locale');
    }

    public static function getPropertyGroupId(string $fieldname): string
    {
        return PimGenerateIdService::getUuid5(PimGenerateIdService::sanitizeName($fieldname), 'property_group');
    }

    public static function getPropertyGroupOptionId(string $fieldname, string $groupId): string
    {
        return PimGenerateIdService::getUuid5($groupId.PimGenerateIdService::sanitizeName($fieldname), 'property_group_option');
    }

    public static function getProductId(?string $identifier = null, ?string $productNumber = null): string
    {
        return PimGenerateIdService::getUuid5('identifier: '.$identifier.'product_number: '.$productNumber, 'product');
    }

    public static function getProductImageId(string $productId, string $url): string
    {
        return PimGenerateIdService::getUuid5($productId.PimGenerateIdService::stripProtocolFromUrl($url), 'product_category');
    }

    public static function getProductManufacturerId(string $name): string
    {
        return PimGenerateIdService::getUuid5(PimGenerateIdService::sanitizeName($name), 'product_manufacturer');
    }

    public static function getCountryId(string $isoCode): string
    {
        return PimGenerateIdService::getUuid5(PimGenerateIdService::sanitizeName($isoCode), 'country');
    }

    public static function getCacheTranslationId(string $provider, string $class, string $fromLang, string $toLang, string $input): string
    {
        return PimGenerateIdService::getUuid5($provider.$class.$fromLang.$toLang.$input, 'cache_translation');
    }

    public static function getCustomerBranchId(string $id): string
    {
        return PimGenerateIdService::getUuid5($id, 'customer_branch');
    }

    public static function getCustomerSalutationId(string $id): string
    {
        return PimGenerateIdService::getUuid5($id, 'customer_salutation');
    }

    public static function getCustomerTaxGroupId(string $id): string
    {
        return PimGenerateIdService::getUuid5($id, 'customer_tax_group');
    }

    public static function getCustomerId(string $id, PimCustomerType $type): string
    {
        return PimGenerateIdService::getUuid5($id, $type->value);
    }

    protected static function getUuid5(string $input, string $namespace): string
    {
        // Use a predefined namespace (or generate a custom one if needed)
        $namespace = Uuid::uuid5(Uuid::NAMESPACE_DNS, $namespace);

        // Generate a reproducible UUID v5 based on the namespace and input
        return Uuid::uuid5($namespace, $input)->toString();
    }

    protected static function sanitizeName($input)
    {
        return Str::lower(trim($input));
    }

    protected static function stripProtocolFromUrl(string $url): string
    {
        // strip protocol from $url
        $parsedUrl = parse_url($url);

        return $parsedUrl['host'].($parsedUrl['path'] ?? '');
    }
}
