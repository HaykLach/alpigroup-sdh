<?php

declare(strict_types=1);

namespace App\Repositories\Language;

use App\Contracts\Language\LanguageRepositoryInterface;
use App\Models\Pim\PimLanguage;
use App\Repositories\BaseRepository;

class LanguageRepository extends BaseRepository implements LanguageRepositoryInterface
{
    public const PIM_LANGUAGE_CACHE_KEY = 'pimLanguage';

    public function findByName(string $name): ?PimLanguage
    {
        return $this->findModelByField(PimLanguage::class, $name, 'name', [], self::PIM_LANGUAGE_CACHE_KEY);
    }

    public function find(string $id): ?PimLanguage
    {
        return $this->findModelByField(PimLanguage::class, $id, 'id', [], self::PIM_LANGUAGE_CACHE_KEY);
    }
}
