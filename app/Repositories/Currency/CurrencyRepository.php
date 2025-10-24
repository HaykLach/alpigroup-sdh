<?php

declare(strict_types=1);

namespace App\Repositories\Currency;

use App\Contracts\Currency\CurrencyRepositoryInterface;
use App\Models\Pim\PimCurrency;
use App\Repositories\BaseRepository;

class CurrencyRepository extends BaseRepository implements CurrencyRepositoryInterface
{
    public const PIM_CURRENCY_CACHE_NAME = 'pimCurrency';

    public function findByIso(string $iso): ?PimCurrency
    {
        return $this->findModelByField(PimCurrency::class, $iso, 'iso_code', [], self::PIM_CURRENCY_CACHE_NAME);
    }

    public function create(array $data): PimCurrency
    {
        $pimCurrency = PimCurrency::create($data);
        $this->saveInCache(self::PIM_CURRENCY_CACHE_NAME, 'iso_code', $pimCurrency);

        return $pimCurrency;
    }

    public function find(string $id): ?PimCurrency
    {
        return $this->findModelByField(PimCurrency::class, $id, 'id', [], self::PIM_CURRENCY_CACHE_NAME);
    }
}
