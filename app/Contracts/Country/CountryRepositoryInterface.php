<?php

namespace App\Contracts\Country;

use App\Models\Pim\Country\PimCountry;

interface CountryRepositoryInterface
{
    public function create(array $data): PimCountry;

    public function findByIso(string $iso, array $relations = []): ?PimCountry;
}
