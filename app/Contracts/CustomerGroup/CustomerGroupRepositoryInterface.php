<?php

namespace App\Contracts\CustomerGroup;

use App\Models\Pim\Customer\PimCustomerGroup;

interface CustomerGroupRepositoryInterface
{
    /**
     * @param array $data
     *
     * @return PimCustomerGroup|null
     */
    public function upsert(array $data): ?PimCustomerGroup;

    /**
     * @param string $id
     * @param array $data
     *
     * @return PimCustomerGroup
     */
    public function update(string $id, array $data): PimCustomerGroup;

    /**
     * get from Db customer group by name
     *
     * @param string $customerGroupName
     *
     * @return PimCustomerGroup|null
     */
    public function findByName(string $customerGroupName): ?PimCustomerGroup;

    /**
     * @param string $id
     *
     * @return PimCustomerGroup|null
     */
    public function findById(string $id): ?PimCustomerGroup;
}
