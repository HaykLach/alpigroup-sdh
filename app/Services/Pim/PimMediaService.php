<?php

namespace App\Services\Pim;

use App\Enums\Pim\PimMappingType;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductTranslation;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PimMediaService
{
    public static function sync(PimProduct $record, Collection $variants): void
    {
        if ($variants->isEmpty()) {
            return;
        }

        // update media for variants
        $mediaItems = self::getMediaCollections($record);

        $variants->each(function (PimProduct $variant) use ($mediaItems) {
            self::handleVariant($variant, $mediaItems);
        });
    }

    private static function getMediaCollections(PimProduct|PimProductTranslation $record)
    {
        return $record->getMedia('*')
            ->groupBy('collection_name')
            ->reject(fn ($media, $collectionName) => $collectionName === PimProduct::getMediaCollectionPreviewName());
    }

    public static function syncTranslation(PimProduct $product, Collection $variants): void
    {
        if ($variants->isEmpty()) {
            return;
        }

        // get languages
        $translationService = new PimTranslationService;
        $extraLangIdCodes = $translationService->getExtraLanguagesShort();

        $extraLangIdCodes->each(function ($langIdCode, $langId) use ($variants, $product) {

            $langVariants = $variants->map(function (PimProduct $variant) use ($langId) {
                return $variant->translations()
                    ->where('language_id', $langId)
                    ->with('media')
                    ->first();
            });

            /** @var PimProductTranslation $productTranslation */
            $productTranslation = $product->translations()
                ->where('language_id', $langId)
                ->with('media')
                ->first();
            $mediaItems = self::getMediaCollections($productTranslation);

            $langVariants->each(function (PimProductTranslation $variant) use ($mediaItems) {
                self::handleVariant($variant, $mediaItems);
            });
        });
    }

    private static function handleVariant(PimProduct|PimProductTranslation $record, MediaCollection $mediaItems): void
    {
        if ($mediaItems->isEmpty()) {
            // delete all media items from the collection
            PimPropertyGroupService::getMediaCollectionIds(PimMappingType::PRODUCT)
                ->each(function ($collection) use ($record) {
                    $record->clearMediaCollection($collection);
                }
                );

            return;
        }

        self::updateMedia($record, $mediaItems);
    }

    private static function updateMedia(PimProduct|PimProductTranslation $record, MediaCollection $mediaItems): void
    {
        // Check if the media item is already attached to avoid duplicates
        $mediaItems->each(function (MediaCollection $group, $collectionName) use ($record) {

            $excludedMedia = collect();

            $group->each(function (Media $mediaItem) use ($record, $collectionName, $excludedMedia) {

                $media = self::checkRecordHasMedia($collectionName, $record, $mediaItem);

                if ($media) {
                    $excludedMedia->push($media);

                    // set order of column
                    if ($media->order_column !== $mediaItem->order_column) {
                        $media->update(['order_column' => $mediaItem->order_column]);
                    }
                } else {
                    $added = $record->addMedia($mediaItem->getPath())
                        ->preservingOriginal()
                        ->setOrder($mediaItem->order_column)
                        ->setName($mediaItem->name)
                        ->toMediaCollection($collectionName);

                    $record->refresh();

                    $excludedMedia->push($added);
                }
            });

            $record->clearMediaCollectionExcept($collectionName, $excludedMedia);
        });

    }

    private static function checkRecordHasMedia(string $collectionName, PimProduct|PimProductTranslation $record, Media $mediaItem): ?Media
    {
        // check if record has media item, compare keys model_type, name, file_name, mime_type, size of both arrays
        return $record->getMedia($collectionName)
            ->first(function (Media $media) use ($mediaItem) {
                return $media->model_type === $mediaItem->model_type
                    && $media->name === $mediaItem->name
                    && $media->file_name === $mediaItem->file_name
                    && $media->mime_type === $mediaItem->mime_type
                    && $media->size === $mediaItem->size;
            });
    }

    public static function getBase64PreviewImage(Media $media): string
    {
        return base64_encode(file_get_contents($media->getPath(PimProduct::getMediaCollectionPreviewName())));
    }
}
