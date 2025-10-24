<?php

namespace App\Contracts\Category;

use App\Models\Pim\PimCategory;

interface CategoryRepositoryInterface
{
    public function findByName(string $name): ?PimCategory;

    public function findByField(string $field, string $value, array $relations = []): ?PimCategory;

    public function create(array $data): PimCategory;

    public function update(string $categoryId, array $data): PimCategory;
}
