<?php

namespace App\Services\Pim\Import;

use App\Enums\Pim\PimProductImageStatus;
use App\Jobs\ProcessPimProductImage;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductImage;
use App\Services\MediaLibrary\CustomPathGenerator;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PimProductImageService
{
    protected string $mediaCollectionName;

    public function __construct()
    {
        $this->mediaCollectionName = PimProduct::getMediaCollectionPreviewName();
    }

    public function addUniqueProductUrls(): void
    {
        PimProduct::query()
            ->chunk(1000, function ($products) {
                $products->each(function ($product) {
                    if (isset($product->images)) {
                        foreach ($product->images as $url) {
                            $this->addEntryIfNotExists($product, $url);
                        }
                    }
                });
            });
    }

    protected function addEntryIfNotExists(PimProduct $product, string $url): void
    {
        if (! PimProductImage::where('url', $url)
            ->where('product_id', $product->id)
            ->exists()
        ) {
            PimProductImage::create([
                'product_id' => $product->id,
                'url' => $url,
            ]);
        }
    }

    public function dispatchJobs(): void
    {
        $this->getPendingUrls()->each(function ($url) {
            ProcessPimProductImage::dispatch($url);
        });

    }

    protected function getPendingUrls(): Collection
    {
        return PimProductImage::select('url')
            ->where('status', PimProductImageStatus::PENDING)
            ->groupBy('url')
            ->get()
            ->pluck('url');
    }

    public function getProductImagesByUrl(string $url): Collection
    {
        // get products with the same image url
        return PimProductImage::where('url', $url)
            ->with([
                'product',
                'product.parent',
            ])
            ->orderBy('product_id')
            ->get();
    }

    public function handleUrl(string $url): void
    {
        $this->processMedia($this->getProductImagesByUrl($url), $url);
    }

    protected function processMedia(Collection $pimProductImageSet, string $url): void
    {
        if ($pimProductImageSet->isEmpty()) {
            return;
        }

        $product = $pimProductImageSet->first()->product;
        if (! $this->checkMediaAlreadyExists($product, $url, $pimProductImageSet)) {
            $this->addMediaToProducts($product, $url, $pimProductImageSet);
        }

        $pimProductImageSet->each(function ($productImage) {
            $productImage->update([
                'status' => PimProductImageStatus::COMPLETE,
            ]);
        });
    }

    protected function addMediaToProducts(PimProduct $product, string $url, Collection $pimProductImageSet): void
    {
        $order = $this->getOrderOfImage($product, $url);

        /** @var Media $media */
        $media = $product
            ->addMediaFromUrl($url)
            // set position of url in collection
            ->setOrder($order)
            ->withCustomProperties([
                CustomPathGenerator::FOLDER_PREFIX_KEY => $this->getFolderPrefix($product),
                CustomPathGenerator::SOURCE_URL => $url,
            ])
            ->toMediaCollection($this->mediaCollectionName);

        $fileHash = $this->getFileHash($media);
        $media->setCustomProperty(CustomPathGenerator::FILE_HASH_KEY, $fileHash);
        $media->save();

        // copy media (only db record) to other product images
        $pimProductImageSet->slice(1)->each(function ($productImage) use ($media) {
            $this->createMediaDuplicate($media, $productImage->product);
        });

        $this->deleteOriginalMediaFile($media);
    }

    protected function getOrderOfImage(PimProduct $product, string $url): int
    {
        return collect($product->images)->search($url) + 1;
    }

    protected function createMediaDuplicate(Media $media, PimProduct $product): void
    {
        $duplicate = $media->replicate()->toArray();
        $duplicate['model_id'] = $product->id;

        unset($duplicate['original_url']);
        unset($duplicate['preview_url']);
        unset($duplicate['uuid']);

        Media::create($duplicate);
    }

    protected function deleteOriginalMediaFile(Media $media): void
    {
        unlink($media->getPath());
    }

    protected function getFileHash(Media $media): string
    {
        return hash_file('md5', $media->getPath());
    }

    protected function checkMediaAlreadyExists(PimProduct $product, string $url, Collection $pimProductImageSet): bool
    {
        $media = $this->getMediaFromMediaCollection($product, $url);

        // check file exists in db and in storage
        if ($media
            && $media->hasGeneratedConversion('preview')
            && $media->hasGeneratedConversion('thumbnail')
        ) {
            if (file_exists($media->getPath('preview'))
                && file_exists($media->getPath('thumbnail'))
            ) {
                return true;

            } else {
                // conversion uncompleted: remove entry from db
                $pimProductImageSet->each(function ($productImage) use ($url) {
                    $productMedia = $this->getMediaFromMediaCollection($productImage->product, $url);
                    $productMedia?->delete();
                });
            }
        }

        return false;
    }

    protected function getMediaFromMediaCollection(PimProduct $product, string $url): ?Media
    {
        /** @var MediaCollection $mediaCollection */
        $mediaCollection = $product->getMedia($this->mediaCollectionName);

        /** @var Media $entry */
        return $mediaCollection->filter(function ($media) use ($url) {
            if ($media->hasCustomProperty(CustomPathGenerator::SOURCE_URL)) {
                return $media->getCustomProperty(CustomPathGenerator::SOURCE_URL) === $url;
            }

            return false;
        })->first();
    }

    protected function getFolderPrefix(PimProduct $product): string
    {
        if ($product->isMainProduct) {
            return CustomPathGenerator::convertFilename(null).'/'.CustomPathGenerator::convertFilename($product->product_number);
        }

        return CustomPathGenerator::convertFilename($product->parent->product_number).'/'.CustomPathGenerator::convertFilename($product->product_number);
    }
}
