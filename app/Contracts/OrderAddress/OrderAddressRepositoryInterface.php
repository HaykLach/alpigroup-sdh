<?php

namespace App\Contracts\OrderAddress;

use App\Models\Pim\Order\PimOrderAddress;

interface OrderAddressRepositoryInterface
{
    public function findByData(array $data): ?PimOrderAddress;

    public function create(array $data): PimOrderAddress;

    public function find(string $id): ?PimOrderAddress;
}
