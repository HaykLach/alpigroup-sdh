<?php

namespace App\Contracts\Order;

use App\Models\Pim\Order\PimOrder;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function create(array $data): PimOrder;

    public function update(string $id, array $data): ?PimOrder;

    public function find(string $id, array $relations = []): ?PimOrder;

    public function findByNumber(string $orderNumber): ?PimOrder;

    public function all(array $relations = []): Collection;

    public function search(?int $offset, ?int $limit, array $relations = []): Collection;
}
