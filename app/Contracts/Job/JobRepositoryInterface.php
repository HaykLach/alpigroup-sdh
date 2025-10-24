<?php

namespace App\Contracts\Job;

use App\Models\Pim\Job\PimJob;

interface JobRepositoryInterface
{
    public function create(array $data): PimJob;

    public function update(string $id, array $data): PimJob;

    public function findByName(string $name): ?PimJob;
}
