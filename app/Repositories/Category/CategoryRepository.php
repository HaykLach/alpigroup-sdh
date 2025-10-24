<?php

declare(strict_types=1);

namespace App\Repositories\Category;

use App\Contracts\Category\CategoryRepositoryInterface;
use App\Models\Pim\PimCategory;
use App\Repositories\BaseRepository;
use Exception;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public const PIM_CATEGORY_CACHE_NAME = 'pimCategory';

    public function findByName(string $name): ?PimCategory
    {
        return $this->findModelByField(PimCategory::class, $name, 'name', [], self::PIM_CATEGORY_CACHE_NAME);
    }

    /**
     * @param  string  $value
     */
    public function findByField(string $field, mixed $value, array $relations = []): ?PimCategory
    {
        return $this->findModelByField(PimCategory::class, $value, $field, $relations, self::PIM_CATEGORY_CACHE_NAME);
    }

    public function create(array $data): PimCategory
    {
        return PimCategory::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $categoryId, array $data): PimCategory
    {
        $pimCategory = $this->findByField('id', $categoryId);
        if (! $pimCategory) {
            throw new Exception('Category with id '.$categoryId.' not found!');
        }

        $pimCategory->update($data);

        return $pimCategory;
    }
}
