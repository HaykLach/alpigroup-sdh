<?php

namespace App\Contracts\Property;

use App\Models\Pim\Property\PimPropertyGroup;

interface PropertyGroupRepositoryInterface
{
    public function find(string $id, array $relations = []): ?PimPropertyGroup;

    public function findByName(string $name, array $relations = []): ?PimPropertyGroup;

    public function create(array $data): PimPropertyGroup;

    public function update(string $id, array $data): PimPropertyGroup;
}
