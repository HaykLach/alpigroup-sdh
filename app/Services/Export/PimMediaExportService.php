<?php

namespace App\Services\Export;

use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Models\Pim\Property\PimPropertyGroup;
use Illuminate\Support\Collection;
use SmartDato\SdhShopwareSdk\Controllers\MediaController;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PimMediaExportService
{
    public static function upload(Media $media, string $mediaFolderId): string
    {
        $mediaController = new MediaController;
        $mediaId = GenerateIdService::getMediaId($media);
        $filename = GenerateIdService::getMediaFilename($media);

        $existingMedia = $mediaController->get($mediaId);
        if ($existingMedia === false) {
            $mediaController->create($mediaId, $mediaFolderId);
        }

        $fileIsEqual = false;
        if ($existingMedia !== false
            && $existingMedia->fileName === $filename
            && $existingMedia->mediaFolderId === $mediaFolderId
            && $existingMedia->fileExtension === $media->extension
            && $existingMedia->fileSize === $media->size
        ) {
            $fileIsEqual = true;
        }

        if (! $fileIsEqual) {
            $mediaController->updateFileUpload($mediaId, $media->getPath(), $filename, $mediaFolderId);
        }

        return $mediaId;
    }

    public static function upsertItemMedia(
        PimProductManufacturer|PimProduct $localEntry,
        PimMappingType $mappingType,
        Collection $locales,
        Collection $pimPropertyGroups,
        string $mediaFolderId
    ): array {
        $customFieldService = new PimCustomFieldsExporterService;

        $mediaData = [];
        if ($mappingType === PimMappingType::MANUFACTURER) {
            $mediaData['mediaId'] = null;
        }

        $pimPropertyGroups->each(function (PimPropertyGroup $pimPropertyGroup) use ($mappingType, $locales, &$mediaData, $localEntry, $customFieldService, $mediaFolderId) {

            $collection = $pimPropertyGroup->custom_fields['collection'];
            $customFieldName = $customFieldService->getCustomFieldName($mappingType, $pimPropertyGroup);

            $mediaData['customFields'][$customFieldName] = null;

            $mediaCollection = $localEntry->media->filter(fn ($media) => $media->collection_name === $collection);
            $mediaCollection->each(function ($media) use (&$mediaData, $customFieldName, $mappingType, $collection, $mediaFolderId) {
                $mediaId = PimMediaExportService::upload($media, $mediaFolderId);
                if ($mappingType === PimMappingType::MANUFACTURER && $collection === 'logo') {
                    $mediaData['mediaId'] = $mediaId;
                } else {
                    $mediaData['customFields'][$customFieldName] = $mediaId;
                }
            });

            $locales->each(function ($code, $languageId) use (&$mediaData, $customFieldName, $collection, $localEntry, $mediaFolderId) {

                $code = PimCustomFieldsExporterService::formatLanguageCode($code);

                $mediaData['translations'][$code]['customFields'][$customFieldName] = null;

                $localEntry->translations->where('language_id', $languageId)->each(function ($translation) use (&$mediaData, $customFieldName, $collection, $code, $mediaFolderId) {
                    $mediaCollection = $translation->media->filter(fn ($media) => $media->collection_name === $collection);
                    $mediaCollection->each(function ($media) use (&$mediaData, $customFieldName, $code, $mediaFolderId) {
                        $mediaId = PimMediaExportService::upload($media, $mediaFolderId);
                        $mediaData['translations'][$code]['customFields'][$customFieldName] = $mediaId;
                    });
                });
            });
        });

        return $mediaData;
    }

    public static function getMediaPropertyGroups(PimMappingType $pimMappingType): Collection
    {
        return PimPropertyGroup::query()
            ->where('custom_fields->type', $pimMappingType->name)
            ->where('custom_fields->form->type', PimFormType::FILEUPLOAD->name)
            ->get();
    }
}
