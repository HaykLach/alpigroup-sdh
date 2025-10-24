<?php

declare(strict_types=1);

namespace App\Repositories\Customer;

use App\Contracts\Customer\CustomerRepositoryInterface;
use App\Models\Pim\Customer\PimCustomer;
use App\Repositories\BaseRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function create(array $data): PimCustomer
    {
        return PimCustomer::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $customerId, array $data): PimCustomer
    {
        $pimCustomer = $this->findModelByField(PimCustomer::class, $customerId, 'id');
        if (! $pimCustomer) {
            throw new Exception('Customer with id '.$customerId.' not found!');
        }

        $pimCustomer->update($data);

        return $pimCustomer;
    }

    public function findByIdentifier(string $identifier, array $relations = []): ?PimCustomer
    {
        return $this->findModelWithRelations(PimCustomer::class, 'identifier', $identifier, $relations)->first();
    }

    public function find(string $id, array $relations = []): ?PimCustomer
    {
        return $this->findModelWithRelations(PimCustomer::class, 'id', $id, $relations)->first();
    }

    public function all(array $relations = []): Collection
    {
        return $this->search(null, null, $relations);
    }

    public function search(?int $offset, ?int $limit, array $relations = []): Collection
    {
        $query = $this->findModelWithRelations(PimCustomer::class, null, null, $relations);

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
