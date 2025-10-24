<?php

declare(strict_types=1);

namespace App\Contracts\Product;

use App\Models\Pim\Product\PimProduct;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function findByIdentifier(string $identifier, array $relations = []): ?PimProduct;

    public function find(string $id, array $relations = []): ?PimProduct;

    public function update(string $id, array $data): PimProduct;

    public function create(array $data): PimProduct;

    public function all(array $relations = []): Collection;

    public function search(?int $offset, ?int $limit, array $relations = []): Collection;
}
