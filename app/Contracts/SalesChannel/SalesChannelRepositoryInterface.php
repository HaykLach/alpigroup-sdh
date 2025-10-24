<?php

namespace App\Contracts\SalesChannel;

use App\Models\Pim\SalesChannel\PimSalesChannel;

interface SalesChannelRepositoryInterface
{
    public function findByName(string $name): ?PimSalesChannel;

    public function create(array $data): PimSalesChannel;
}
