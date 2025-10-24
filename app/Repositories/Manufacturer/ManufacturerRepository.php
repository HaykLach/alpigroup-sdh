<?php

namespace App\Repositories\Manufacturer;

use App\Contracts\Manufacturer\ManufacturerRepositoryInterface;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Repositories\BaseRepository;

class ManufacturerRepository extends BaseRepository implements ManufacturerRepositoryInterface
{
    public const PIM_MANUFACTURER_CACHE_NAME = 'pimManufacturer';

    public function find(string $id): ?PimProductManufacturer
    {
        return $this->findModelByField(PimProductManufacturer::class, $id, 'id', [], self::PIM_MANUFACTURER_CACHE_NAME);
    }

    public function findByName(string $name): ?PimProductManufacturer
    {
        return $this->findModelByField(PimProductManufacturer::class, $name, 'name', [], self::PIM_MANUFACTURER_CACHE_NAME);
    }

    public function create(array $data): PimProductManufacturer
    {
        $pimManufacturer = PimProductManufacturer::create($data);

        $this->saveInCache(self::PIM_MANUFACTURER_CACHE_NAME, $data['name'], $pimManufacturer);

        return $pimManufacturer;
    }
}
