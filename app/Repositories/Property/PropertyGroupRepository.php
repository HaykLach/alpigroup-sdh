<?php

declare(strict_types=1);

namespace App\Repositories\Property;

use App\Contracts\Property\PropertyGroupRepositoryInterface;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Repositories\BaseRepository;
use Exception;

class PropertyGroupRepository extends BaseRepository implements PropertyGroupRepositoryInterface
{
    public function find(string $id, array $relations = []): ?PimPropertyGroup
    {
        return $this->findModelByField(PimPropertyGroup::class, $id, 'id', $relations);
    }

    public function findByName(string $name, array $relations = []): ?PimPropertyGroup
    {
        return $this->findModelByField(PimPropertyGroup::class, $name, 'name', $relations);
    }

    public function create(array $data): PimPropertyGroup
    {
        return PimPropertyGroup::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimPropertyGroup
    {
        $pimPropertyGroup = $this->find($id);
        if (! $pimPropertyGroup) {
            throw new Exception('Property group with id '.$id.' not found');
        }

        $pimPropertyGroup->update($data);

        return $pimPropertyGroup;
    }
}
