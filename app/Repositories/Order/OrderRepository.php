<?php

namespace App\Repositories\Order;

use App\Contracts\Order\OrderRepositoryInterface;
use App\Models\Pim\Order\PimOrder;
use App\Repositories\BaseRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function create(array $data): PimOrder
    {
        return PimOrder::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): ?PimOrder
    {
        $pimOrder = $this->find($id);
        if (! $pimOrder) {
            throw new Exception('Order with id '.$id.' not found');
        }

        $pimOrder->update($data);

        return $pimOrder;
    }

    public function find(string $id, array $relations = []): ?PimOrder
    {
        return $this->findModelByField(PimOrder::class, $id, 'id', $relations);
    }

    public function findByNumber(string $orderNumber): ?PimOrder
    {
        return $this->findModelByField(PimOrder::class, $orderNumber, 'order_number');
    }

    public function all(array $relations = []): Collection
    {
        return $this->search(null, null, $relations);
    }

    public function search(?int $offset, ?int $limit, array $relations = []): Collection
    {
        $query = $this->findModelWithRelations(PimOrder::class, null, null, $relations);

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
