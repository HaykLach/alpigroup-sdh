<?php

declare(strict_types=1);

namespace App\Repositories\Country;

use App\Contracts\Country\CountryRepositoryInterface;
use App\Models\Pim\Country\PimCountry;
use App\Repositories\BaseRepository;

class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{
    /** @var string */
    public const PIM_COUNTRY_CACHE_NAME = 'pimCountry';

    public function create(array $data): PimCountry
    {
        $pimCountry = PimCountry::create($data);
        $this->saveInCache(self::PIM_COUNTRY_CACHE_NAME, $data['iso'], $pimCountry);

        return $pimCountry;
    }

    public function findByIso(string $iso, array $relations = []): ?PimCountry
    {
        return $this->findModelByField(PimCountry::class, $iso, 'iso', $relations, self::PIM_COUNTRY_CACHE_NAME);
    }
}
