<?php

namespace App\Controllers\Export;

use App\Models\Pim\PimTax;
use SmartDato\Shopware6\App\Models\Shopware6Tax\Shopware6TaxExtension;

class PimTaxExporter
{
    protected const string UNIQUE_REMOTE_KEY = 'tax_rate';

    protected const string UNIQUE_LOCAL_KEY = 'tax_rate';

    public function __construct(protected $sdk) {}

    public function sync(): void
    {
        $remoteEntries = $this->sdk->list();
        $localEntries = PimTax::all();

        $this->syncMatchingEntries($localEntries, $remoteEntries);
        $this->createMissingRemoteEntries($localEntries, $remoteEntries);
        $this->removeOrphanedEntries();
    }

    protected function syncMatchingEntries($localEntries, $remoteEntries): void
    {
        $localMatchingRemote = $localEntries->filter(fn ($localEntry) => $remoteEntries->contains(self::UNIQUE_REMOTE_KEY, '=', $localEntry->{self::UNIQUE_LOCAL_KEY}));
        $localMatchingRemote->each(function ($localEntry) use ($remoteEntries) {

            $remoteEntry = $remoteEntries->firstWhere(self::UNIQUE_REMOTE_KEY, $localEntry->{self::UNIQUE_LOCAL_KEY});

            // @todo move specific logic to sdk
            // $this->sdk->tableUpsert($localEntry->id, $remoteEntry->id);
            Shopware6TaxExtension::firstOrCreate(
                [
                    'pim_tax_id' => $localEntry->id,
                    'shopware_tax_id' => $remoteEntry->id,
                ]
            );
        });
    }

    protected function createMissingRemoteEntries($localEntries, $remoteEntries): void
    {
        $localNotMatchingRemote = $localEntries->reject(fn ($localEntry) => $remoteEntries->contains(self::UNIQUE_REMOTE_KEY, '=', $localEntry->{self::UNIQUE_LOCAL_KEY}));
        $localNotMatchingRemote->each(function ($localEntry) {

            $newEntry = $this->sdk->create($localEntry->{self::UNIQUE_LOCAL_KEY}, $localEntry->name, $localEntry->position);

            Shopware6TaxExtension::firstOrCreate(
                [
                    'pim_tax_id' => $localEntry->id,
                    'shopware_tax_id' => $newEntry->id,
                ]
            );
        });
    }

    protected function removeOrphanedEntries(): void
    {
        $remoteEntries = $this->sdk->list();
        $remoteEntryIds = $remoteEntries->pluck('id');

        Shopware6TaxExtension::whereNotIn('shopware_tax_id', $remoteEntryIds)->delete();
    }
}
