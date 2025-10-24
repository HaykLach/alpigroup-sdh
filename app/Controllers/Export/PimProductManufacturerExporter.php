<?php

namespace App\Controllers\Export;

use App\Enums\Pim\PimMappingType;
use App\Jobs\ProcessPimExportManufacturer;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Services\Export\GenerateIdService;
use App\Services\Export\PimMediaExportService;
use App\Services\Pim\PimTranslationService;
use Illuminate\Support\Collection;
use SmartDato\SdhShopwareSdk\Controllers\ProductManufacturerController;
use SmartDato\SdhShopwareSdk\DataTransferObjects\ProductManufacturer;
use SmartDato\Shopware6\App\Models\Shopware6Manufacturer\Shopware6ManufacturerExtension;

class PimProductManufacturerExporter
{
    protected const string UNIQUE_REMOTE_KEY = 'name';

    protected const string UNIQUE_LOCAL_KEY = 'name';

    public function __construct(protected ProductManufacturerController $sdk) {}

    public function sync(): void
    {
        $localEntries = PimProductManufacturer::query()
            ->with('media')
            ->with('translations')
            ->with('translations.media')
            ->get();

        $remoteEntries = $this->sdk->list();

        $this->syncMatchingEntries($localEntries, $remoteEntries);
        $addedNewItems = $this->createMissingRemoteEntries($localEntries, $remoteEntries);
        if ($addedNewItems) {
            $remoteEntries = $this->sdk->list();
        }
        $this->removeOrphanedEntries($remoteEntries);

        $mediaFolderId = config('sdh-shopware-sdk.defaults.mediaFolderId');

        $this->syncData($localEntries, $remoteEntries, PimMappingType::MANUFACTURER, $mediaFolderId);
    }

    protected function syncData(Collection $localEntries, Collection $remoteEntries, PimMappingType $mappingType, string $mediaFolderId): void
    {
        $pimPropertyGroups = PimMediaExportService::getMediaPropertyGroups($mappingType);
        $locales = (new PimTranslationService)->getExtraLanguages();

        $localEntries->each(function ($localEntry) use ($mappingType, $locales, $remoteEntries, $pimPropertyGroups, $mediaFolderId) {

            $localName = GenerateIdService::sanitizeName($localEntry->{self::UNIQUE_LOCAL_KEY});
            $remoteEntry = $remoteEntries->filter(fn ($remoteEntry) => GenerateIdService::sanitizeName($remoteEntry->{self::UNIQUE_REMOTE_KEY}) === $localName)->first();

            if (1) {
                ProcessPimExportManufacturer::dispatch($localEntry, $remoteEntry, $mappingType, $locales, $pimPropertyGroups, $mediaFolderId);
            } else {
                $this->syncMediaData($localEntry, $remoteEntry, $mappingType, $locales, $pimPropertyGroups, $mediaFolderId);
                $this->syncNameData($localEntry, $remoteEntry);
            }
        });
    }

    protected function syncNameData($localEntry, $remoteEntry): void
    {
        if ($localEntry->name !== $remoteEntry->name) {
            $this->assignData(
                $remoteEntry->id,
                ['name' => $localEntry->name]
            );
        }
    }

    protected function syncMediaData(PimProductManufacturer $localEntry, ProductManufacturer $remoteEntry, PimMappingType $mappingType, Collection $locales, Collection $pimPropertyGroups, string $mediaFolderId): void
    {
        $mediaData = PimMediaExportService::upsertItemMedia($localEntry, $mappingType, $locales, $pimPropertyGroups, $mediaFolderId);

        $this->assignData($remoteEntry->id, $mediaData);
    }

    protected function assignData(string $remoteManufacturerId, array $data): void
    {
        $this->sdk->update($remoteManufacturerId, $data);
    }

    protected function syncMatchingEntries($localEntries, $remoteEntries): void
    {
        $localMatchingRemote = $localEntries->filter(function ($localEntry) use ($remoteEntries) {
            return $remoteEntries->contains(function ($remoteEntry) use ($localEntry) {
                return GenerateIdService::sanitizeName($remoteEntry->{self::UNIQUE_REMOTE_KEY}) === GenerateIdService::sanitizeName($localEntry->{self::UNIQUE_LOCAL_KEY});
            });
        });

        $localMatchingRemote->each(function ($localEntry) use ($remoteEntries) {

            $remoteEntry = $remoteEntries->filter(function ($remoteEntry) use ($localEntry) {
                return GenerateIdService::sanitizeName($remoteEntry->{self::UNIQUE_REMOTE_KEY}) === GenerateIdService::sanitizeName($localEntry->{self::UNIQUE_LOCAL_KEY});
            })->first();

            Shopware6ManufacturerExtension::firstOrCreate(
                [
                    'pim_manufacturer_id' => $localEntry->id,
                    'shopware_manufacturer_id' => $remoteEntry->id,
                ]
            );
        });
    }

    protected function createMissingRemoteEntries(Collection $localEntries, Collection $remoteEntries): bool
    {
        $addedNewItems = false;
        $localNotMatchingRemote = $localEntries->reject(function ($localEntry) use ($remoteEntries) {
            return $remoteEntries->contains(function ($remoteEntry) use ($localEntry) {
                return GenerateIdService::sanitizeName($remoteEntry->{self::UNIQUE_REMOTE_KEY}) === GenerateIdService::sanitizeName($localEntry->{self::UNIQUE_LOCAL_KEY});
            });
        });

        $localNotMatchingRemote->each(function ($localEntry) use (&$addedNewItems) {

            $newEntry = $this->sdk->create($localEntry->name);

            Shopware6ManufacturerExtension::firstOrCreate(
                [
                    'pim_manufacturer_id' => $localEntry->id,
                    'shopware_manufacturer_id' => $newEntry->id,
                ]
            );

            $addedNewItems = true;
        });

        return $addedNewItems;
    }

    protected function removeOrphanedEntries(Collection $remoteEntries): void
    {
        Shopware6ManufacturerExtension::whereNotIn('shopware_manufacturer_id', $remoteEntries->pluck('id'))->delete();
    }
}
