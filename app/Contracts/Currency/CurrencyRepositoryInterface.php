<?php

namespace App\Contracts\Currency;

use App\Models\Pim\PimCurrency;

interface CurrencyRepositoryInterface
{
    public function findByIso(string $iso): ?PimCurrency;

    public function create(array $data): PimCurrency;

    public function find(string $id): ?PimCurrency;
}
