<?php

namespace App\Repositories\CustomerGroup;

use App\Contracts\CustomerGroup\CustomerGroupRepositoryInterface;
use App\Models\Pim\Customer\PimCustomerGroup;
use App\Repositories\BaseRepository;
use Exception;

class CustomerGroupRepository extends BaseRepository implements CustomerGroupRepositoryInterface
{
    public const PIM_CUSTOMER_GROUP_CACHE_NAME = 'pimCustomerGroup';

    /**
     * @param array $data
     *
     * @return PimCustomerGroup
     */
    public function upsert(array $data): PimCustomerGroup
    {
        $existingCustomerGroup = $this->findByName($data['name']);
        if ($existingCustomerGroup) {
            return $existingCustomerGroup;
        }

        return PimCustomerGroup::create($data);
    }

    /**
     * @param string $id
     * @param array $data
     *
     * @return PimCustomerGroup
     * @throws Exception
     */
    public function update(string $id, array $data): PimCustomerGroup
    {
        $customerGroup = $this->findById($id);
        if (!$customerGroup) {
            throw new Exception('Customer group with id ' . $id . ' not found');
        }

        $customerGroup->update($data);

        return $customerGroup;
    }

    /**
     * @param string $customerGroupName
     *
     * @return PimCustomerGroup|null
     */
    public function findByName(string $customerGroupName): ?PimCustomerGroup
    {
        /** @var PimCustomerGroup */
        return $this->findModelByField(PimCustomerGroup::class, $customerGroupName, 'name', [], self::PIM_CUSTOMER_GROUP_CACHE_NAME);
    }

    /**
     * @param string $id
     *
     * @return PimCustomerGroup|null
     */
    public function findById(string $id): ?PimCustomerGroup
    {
        /** @var PimCustomerGroup */
        return $this->findModelByField(PimCustomerGroup::class, $id, 'id', [], self::PIM_CUSTOMER_GROUP_CACHE_NAME);
    }
}
