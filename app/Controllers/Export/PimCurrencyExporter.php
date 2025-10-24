<?php

namespace App\Controllers\Export;

use App\Models\Pim\PimCurrency;
use Illuminate\Support\Collection;
use SmartDato\Shopware6\App\Models\Shopware6Currency\Shopware6CurrencyExtension;

class PimCurrencyExporter
{
    protected const string UNIQUE_REMOTE_KEY = 'isoCode';

    protected const string UNIQUE_LOCAL_KEY = 'iso_code';

    public function __construct(protected $sdk) {}

    public function sync(): void
    {
        $remoteEntries = $this->sdk->list();
        $localEntries = PimCurrency::all();

        $this->syncMatchingEntries($localEntries, $remoteEntries);
        $this->createMissingRemoteEntries($localEntries, $remoteEntries);
        $this->removeOrphanedEntries();
    }

    protected function syncMatchingEntries(Collection $localEntries, Collection $remoteEntries): void
    {
        $localMatchingRemote = $localEntries->filter(fn ($localEntry) => $remoteEntries->contains(self::UNIQUE_REMOTE_KEY, '=', $localEntry->{self::UNIQUE_LOCAL_KEY}));
        $localMatchingRemote->each(function ($localEntry) use ($remoteEntries) {

            $remoteEntry = $remoteEntries->firstWhere(self::UNIQUE_REMOTE_KEY, $localEntry->{self::UNIQUE_LOCAL_KEY});

            // @todo move specific logic to sdk
            // $this->sdk->tableUpsert($localEntry->id, $remoteEntry->id);
            Shopware6CurrencyExtension::firstOrCreate(
                [
                    'pim_currency_id' => $localEntry->id,
                    'shopware_currency_id' => $remoteEntry->id,
                ]
            );
        });
    }

    protected function createMissingRemoteEntries($localEntries, $remoteEntries): void
    {
        $localNotMatchingRemote = $localEntries->reject(fn ($localEntry) => $remoteEntries->contains(self::UNIQUE_REMOTE_KEY, '=', $localEntry->{self::UNIQUE_LOCAL_KEY}));

        $localNotMatchingRemote->each(function ($localEntry) {

            // @todo move to create sdk
            $newEntry = $this->sdk->create($localEntry->{self::UNIQUE_LOCAL_KEY}, $localEntry->{self::UNIQUE_LOCAL_KEY}, $localEntry->short_name, $localEntry->name);
            if ($newEntry === false) {
                return;
            }

            Shopware6CurrencyExtension::firstOrCreate(
                [
                    'pim_currency_id' => $localEntry->id,
                    'shopware_currency_id' => $newEntry->id,
                ]
            );
        });
    }

    protected function removeOrphanedEntries(): void
    {
        $remoteEntries = $this->sdk->list();
        $remoteEntryIds = $remoteEntries->pluck('id');

        Shopware6CurrencyExtension::whereNotIn('shopware_currency_id', $remoteEntryIds)->delete();
    }
}
