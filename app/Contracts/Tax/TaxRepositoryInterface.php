<?php

namespace App\Contracts\Tax;

use App\Models\Pim\PimTax;

interface TaxRepositoryInterface
{
    public function create(array $data): PimTax;

    public function update(string $id, array $data): PimTax;

    public function findByName(string $name): ?PimTax;

    public function find(string $id): ?PimTax;

    public function findDefault(): ?PimTax;
}
