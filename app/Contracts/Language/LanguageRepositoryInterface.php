<?php

namespace App\Contracts\Language;

use App\Models\Pim\PimLanguage;

interface LanguageRepositoryInterface
{
    public function findByName(string $name): ?PimLanguage;

    public function find(string $id): ?PimLanguage;
}
