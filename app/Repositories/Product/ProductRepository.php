<?php

declare(strict_types=1);

namespace App\Repositories\Product;

use App\Contracts\Product\ProductRepositoryInterface;
use App\Models\Pim\Product\PimProduct;
use App\Repositories\BaseRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function findByIdentifier(string $identifier, array $relations = [], bool $trashed = false): ?PimProduct
    {
        $query = $this->findModelWithRelations(PimProduct::class, 'identifier', $identifier, $relations);
        if ($trashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    public function find(string $id, array $relations = [], bool $trashed = false): ?PimProduct
    {
        $query = $this->findModelWithRelations(PimProduct::class, 'id', $id, $relations);
        if ($trashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimProduct
    {
        $pimProduct = $this->find($id, [], true);
        if (! $pimProduct) {
            throw new Exception('Product with id '.$id.' not found!');
        }

        $pimProduct->update($data);

        return $pimProduct;
    }

    public function create(array $data): PimProduct
    {
        return PimProduct::create($data);
    }

    public function all(array $relations = []): Collection
    {
        return $this->search(null, null, $relations);
    }

    public function search(?int $offset, ?int $limit, array $relations = [], bool $trashed = false): Collection
    {
        $query = $this->findModelWithRelations(PimProduct::class, null, null, $relations);

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
