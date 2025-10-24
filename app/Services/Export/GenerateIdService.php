<?php

namespace App\Services\Export;

use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use Illuminate\Support\Str;
use SmartDato\SdhShopwareSdk\Services\ShopwareIdService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GenerateIdService
{
    public static function getPropertyGroupOptionId(PimPropertyGroup $pimPropertyGroup, PimPropertyGroupOption $pimPropertyGroupOption): string
    {
        return ShopwareIdService::getUuid5($pimPropertyGroup->name.$pimPropertyGroupOption->name);
    }

    public static function getPropertyGroupId(PimPropertyGroup $pimPropertyGroup): string
    {
        return ShopwareIdService::getUuid5($pimPropertyGroup->name);
    }

    public static function getProductId(PimProduct $pimProduct): string
    {
        return ShopwareIdService::getUuid5(GenerateIdService::getProductStructure($pimProduct));
    }

    public static function getUrlMediaId(string $url): string
    {
        return ShopwareIdService::getUuid5($url.'media_entity');
    }

    public static function getMediaId(Media $media): string
    {
        return ShopwareIdService::getUuid5(GenerateIdService::getMediaFilename($media).'media_entity');
    }

    public static function getMediaFilename(Media $media): string
    {
        return $media->name.'_'.$media->collection_name;
    }

    public static function getProductMediaExportId(PimProduct $pimProduct, int $pos = 1): string
    {
        return ShopwareIdService::getUuid5(GenerateIdService::getProductStructure($pimProduct).$pos.'product_media_entity');
    }

    public static function getCustomFieldSetId(string $fieldname): string
    {
        return ShopwareIdService::getUuid5($fieldname);
    }

    public static function getCustomFieldSetRelationId(string $fieldname): string
    {
        return ShopwareIdService::getUuid5($fieldname.'relation');
    }

    public static function getCustomFieldId(PimPropertyGroup $pimPropertyGroup): string
    {
        return ShopwareIdService::getUuid5($pimPropertyGroup->custom_fields['type'].$pimPropertyGroup->name);
    }

    public static function getProductVisibilityId(PimProduct $pimProduct, string $salesChannelId): string
    {
        return ShopwareIdService::getUuid5(GenerateIdService::getProductStructure($pimProduct).$salesChannelId);
    }

    protected static function getProductStructure(PimProduct $pimProduct): string
    {
        return 'product_number'.$pimProduct->product_number.'identifier'.$pimProduct->identifier;
    }

    public static function sanitizeName($input)
    {
        return Str::lower(trim($input));
    }
}
