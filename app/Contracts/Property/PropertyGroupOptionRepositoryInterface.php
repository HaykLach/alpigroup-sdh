<?php

namespace App\Contracts\Property;

use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;

interface PropertyGroupOptionRepositoryInterface
{
    public function find(string $id, array $relations = []): ?PimPropertyGroupOption;

    public function create(array $data): PimPropertyGroupOption;

    public function update(string $id, array $data): PimPropertyGroupOption;

    public function findByName(string $name, array $relations = []): ?PimPropertyGroupOption;
}
