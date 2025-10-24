<?php

namespace App\Repositories\OrderAddress;

use App\Contracts\OrderAddress\OrderAddressRepositoryInterface;
use App\Models\Pim\Order\PimOrderAddress;
use App\Repositories\BaseRepository;

class OrderAddressRepository extends BaseRepository implements OrderAddressRepositoryInterface
{
    public function findByData(array $data): ?PimOrderAddress
    {
        return PimOrderAddress::where([
            ['order_id', '=', $data['order_id']],
            ['zipcode', '=', $data['zipcode']],
            ['country_id', '=', $data['country_id']],
            ['city', '=', $data['city']],
            ['street', '=', $data['street']],
            ['additional_address_line_1', '=', $data['additional_address_line_1']],
            ['phone_number', '=', $data['phone_number']],
            ['region', '=', $data['region']],
            ['vat_id', '=', $data['vat_id']],
            ['first_name', '=', $data['first_name']],
            ['last_name', '=', $data['last_name']],
            ['salutation_id', '=', $data['salutation_id']],
        ])->first();
    }

    public function create(array $data): PimOrderAddress
    {
        return PimOrderAddress::create($data);
    }

    public function find(string $id): ?PimOrderAddress
    {
        return $this->findModelByField(PimOrderAddress::class, $id, 'id');
    }
}
