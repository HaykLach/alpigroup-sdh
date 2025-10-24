<?php

namespace App\Repositories\Salutation;

use App\Contracts\Salutation\SalutationRepositoryInterface;
use App\Models\Pim\Customer\PimCustomerSalutation;
use App\Repositories\BaseRepository;
use Exception;

class SalutationRepository extends BaseRepository implements SalutationRepositoryInterface
{
    public const PIM_SALUTATION_CACHE_NAME = 'pimSalutation';

    public function findByKey(string $key): ?PimCustomerSalutation
    {
        return $this->findModelByField(PimCustomerSalutation::class, $key, 'salutation_key', [], self::PIM_SALUTATION_CACHE_NAME);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimCustomerSalutation
    {
        $pimSalutation = $this->find($id);
        if (! $pimSalutation) {
            throw new Exception('Salutation with id '.$id.' not found!');
        }

        $pimSalutation->update($data);

        return $pimSalutation;
    }

    public function create(array $data): PimCustomerSalutation
    {
        return PimCustomerSalutation::create($data);
    }

    public function find(string $id): ?PimCustomerSalutation
    {
        return $this->findModelByField(PimCustomerSalutation::class, $id, 'id', [], self::PIM_SALUTATION_CACHE_NAME);
    }
}
