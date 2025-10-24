<?php

declare(strict_types=1);

namespace App\Repositories\Property;

use App\Contracts\Property\PropertyGroupOptionRepositoryInterface;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Repositories\BaseRepository;
use Exception;

class PropertyGroupOptionRepository extends BaseRepository implements PropertyGroupOptionRepositoryInterface
{
    public function find(string $id, array $relations = []): ?PimPropertyGroupOption
    {
        return $this->findModelByField(PimPropertyGroupOption::class, $id, 'id', $relations);
    }

    public function create(array $data): PimPropertyGroupOption
    {
        return PimPropertyGroupOption::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimPropertyGroupOption
    {
        $pimPropertyGroupOption = $this->find($id);
        if (! $pimPropertyGroupOption) {
            throw new Exception('Property group option with id '.$id.' not found!');
        }

        $pimPropertyGroupOption->update($data);

        return $pimPropertyGroupOption;
    }

    public function findByName(string $name, array $relations = []): ?PimPropertyGroupOption
    {
        return $this->findModelByField(PimPropertyGroupOption::class, $name, 'name', $relations);
    }
}
