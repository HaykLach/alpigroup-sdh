<?php

declare(strict_types=1);

namespace App\Contracts\Customer;

use App\Models\Pim\Customer\PimCustomer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    public function create(array $data): PimCustomer;

    public function update(string $customerId, array $data): PimCustomer;

    public function findByIdentifier(string $identifier, array $relations = []): ?PimCustomer;

    public function find(string $id, array $relations = []): ?PimCustomer;

    public function all(array $relations = []): Collection;

    public function search(?int $offset, ?int $limit, array $relations = []): Collection;
}
