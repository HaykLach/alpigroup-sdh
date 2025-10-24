<?php

declare(strict_types=1);

namespace App\Repositories\Tax;

use App\Contracts\Tax\TaxRepositoryInterface;
use App\Models\Pim\PimTax;
use App\Repositories\BaseRepository;
use Exception;

class TaxRepository extends BaseRepository implements TaxRepositoryInterface
{
    public function create(array $data): PimTax
    {
        return PimTax::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimTax
    {
        $pimTax = $this->find($id);
        if (! $pimTax) {
            throw new Exception('Tax with id '.$id.' not found');
        }

        $pimTax->update($data);

        return $pimTax;
    }

    public function findByName(string $name): ?PimTax
    {
        return $this->findModelByField(PimTax::class, $name, 'name');
    }

    public function find(string $id): ?PimTax
    {
        return $this->findModelByField(PimTax::class, $id, 'id');
    }

    public function findDefault(): ?PimTax
    {
        return $this->findModelByField(PimTax::class, 1, 'is_default');
    }
}
