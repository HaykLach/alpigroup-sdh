<?php

namespace App\Contracts\Manufacturer;

use App\Models\Pim\Product\PimProductManufacturer;

interface ManufacturerRepositoryInterface
{
    public function find(string $id): ?PimProductManufacturer;

    public function findByName(string $name): ?PimProductManufacturer;

    public function create(array $data): PimProductManufacturer;
}
