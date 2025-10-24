<?php

namespace App\Contracts\CustomerAddress;

use App\Models\Pim\Customer\PimCustomerAddress;

interface CustomerAddressRepositoryInterface
{
    public function findByData(array $data): ?PimCustomerAddress;

    public function create(array $data): PimCustomerAddress;
}
