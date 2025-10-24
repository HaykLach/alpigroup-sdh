<?php

declare(strict_types=1);

namespace App\Repositories\SalesChannel;

use App\Contracts\SalesChannel\SalesChannelRepositoryInterface;
use App\Models\Pim\SalesChannel\PimSalesChannel;
use App\Repositories\BaseRepository;

class SalesChannelRepository extends BaseRepository implements SalesChannelRepositoryInterface
{
    public function findByName(string $name): ?PimSalesChannel
    {
        return $this->findModelByField(PimSalesChannel::class, $name, 'name');
    }

    public function create(array $data): PimSalesChannel
    {
        return PimSalesChannel::create($data);
    }
}
