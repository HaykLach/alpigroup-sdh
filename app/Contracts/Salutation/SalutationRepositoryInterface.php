<?php

namespace App\Contracts\Salutation;

use App\Models\Pim\Customer\PimCustomerSalutation;

interface SalutationRepositoryInterface
{
    public function findByKey(string $key): ?PimCustomerSalutation;

    public function update(string $id, array $data): PimCustomerSalutation;

    public function create(array $data): PimCustomerSalutation;

    public function find(string $id): ?PimCustomerSalutation;
}
