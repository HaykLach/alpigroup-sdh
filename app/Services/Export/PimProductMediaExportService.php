<?php

namespace App\Services\Export;

use App\Jobs\ProcessPimExportProductMedia;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\MediaLibrary\CustomPathGenerator;
use SmartDato\SdhShopwareSdk\Controllers\MediaController;
use SmartDato\SdhShopwareSdk\DataTransferObjects\Product;

class PimProductMediaExportService
{
    protected array $cachedExistingMediaIds = [];

    protected function cacheRegisterMedia(string $id): void
    {
        $this->cachedExistingMediaIds[$id] = true;
    }

    protected function checkIfMediaRequestExists(string $id): bool
    {
        return $this->cachedExistingMediaIds[$id] ?? false;
    }

    protected static function checkHasNoImages(PimProduct $pimProduct): bool
    {
        return empty($pimProduct->images) || count($pimProduct->images) === 0;
    }

    public static function assignCoverImage(PimProduct $pimProduct): ?string
    {
        if (PimProductMediaExportService::checkHasNoImages($pimProduct)) {
            return null;
        }

        return GenerateIdService::getProductMediaExportId($pimProduct);
    }

    public static function getProductImageMediaEntities(PimProduct $pimProduct, ?string $configMediaFolderId = null): array
    {
        if (PimProductMediaExportService::checkHasNoImages($pimProduct)) {
            return [];
        }

        $media = [];
        foreach ($pimProduct->images as $key => $url) {
            $mediaId = GenerateIdService::getUrlMediaId($url);
            $pos = $key + 1;
            $productMediaId = GenerateIdService::getProductMediaExportId($pimProduct, $pos);
            $title = e($pimProduct->name);
            $mediaItem = [
                'id' => $productMediaId,
                'media' => [
                    'id' => $mediaId,
                    'mediaFolderId' => $configMediaFolderId,
                    // 'alt' => $title,
                    'title' => $title,
                ],
                'position' => $pos,
                'title' => $pimProduct->name,
            ];
            if ($configMediaFolderId) {
                $mediaItem['media']['mediaFolderId'] = $configMediaFolderId;
            }
            $media[] = $mediaItem;
        }

        return $media;
    }

    public function dispatchProductImagesJobs(Product|false $existingProduct, PimProduct $pimProduct, ?PimProduct $pimProductMain = null): void
    {
        // assign existing media to cache
        $this->assignExistingMediaToCache($existingProduct);

        if ($pimProduct->variations->isNotEmpty()) {
            $pimProduct->variations->each(function (PimProduct $variant) use ($pimProduct) {
                $this->dispatchProductImagesJobs(false, $variant, $pimProduct);
            });
        }

        if (PimProductMediaExportService::checkHasNoImages($pimProduct)) {
            return;
        }

        foreach ($pimProduct->images as $key => $url) {
            $id = GenerateIdService::getUrlMediaId($url);
            $exists = $this->checkIfMediaRequestExists($id);

            if ($exists === false) {
                $pos = $key + 1;
                $filename = $this->getImageFilename($pimProduct->manufacturer, $pimProduct, $pos, $pimProductMain);

                if (1) {
                    ProcessPimExportProductMedia::dispatch($id, $url, $filename);
                } else {
                    $this->handleImageRequest($id, $url, $filename);
                }

                $this->cacheRegisterMedia($id);
            }
        }
    }

    protected function assignExistingMediaToCache(Product|false $existingProduct): void
    {
        if ($existingProduct !== false) {
            if (isset($existingProduct->media)) {
                collect($existingProduct->media)->each(function (array $media) {
                    if ($this->isMediaValid($media['media'])) {
                        $this->cacheRegisterMedia($media['mediaId']);
                    }
                });
            }

            if (isset($existingProduct->children)) {
                foreach ($existingProduct->children as $child) {
                    if (isset($child['media'])) {
                        collect($child['media'])->each(function (array $media) {
                            if ($this->isMediaValid($media['media'])) {
                                $this->cacheRegisterMedia($media['mediaId']);
                            }
                        });
                    }
                }
            }
        }
    }

    protected function getImageFilename(PimProductManufacturer $manufacturer, PimProduct $pimProduct, int $position, ?PimProduct $pimProductMain = null): string
    {
        $manufacturerName = CustomPathGenerator::convertFilename($manufacturer->name);
        $productNumber = CustomPathGenerator::convertFilename($pimProduct->product_number);

        if ($pimProduct->isMainProduct) {
            return "{$manufacturerName}_{$productNumber}_{$position}";
        }

        $parentProductNumber = CustomPathGenerator::convertFilename($pimProductMain->product_number);

        return "{$manufacturerName}_{$parentProductNumber}_{$productNumber}_{$position}";
    }

    public function handleImageRequest(string $id, string $url, string $filename): bool
    {
        $mediaController = new MediaController;
        $response = $mediaController->update($id, $url, $filename);
        if ($response->failed()) {
            return false;
        }

        return true;
    }

    protected function isMediaValid(array $media): bool
    {
        return $media['fileSize'] !== null && $media['fileSize'] > 0;
    }
}
