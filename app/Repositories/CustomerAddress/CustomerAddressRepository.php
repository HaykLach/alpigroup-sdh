<?php

namespace App\Repositories\CustomerAddress;

use App\Contracts\CustomerAddress\CustomerAddressRepositoryInterface;
use App\Models\Pim\Customer\PimCustomerAddress;
use App\Repositories\BaseRepository;

class CustomerAddressRepository extends BaseRepository implements CustomerAddressRepositoryInterface
{
    public function findByData(array $data): ?PimCustomerAddress
    {
        $query = PimCustomerAddress::query()->where([
            ['customer_id', '=', $data['customer_id']],
            ['zipcode', '=', $data['zipcode']],
            ['country_id', '=', $data['country_id']],
            ['city', '=', $data['city']],
            ['street', '=', $data['street']],
            ['additional_address_line_1', '=', $data['additional_address_line_1'] ?? ''],
            ['phone_number', '=', $data['phone_number'] ?? ''],
            ['first_name', '=', $data['first_name']],
            ['last_name', '=', $data['last_name']],
            ['salutation_id', '=', $data['salutation_id']],
            ['custom_fields', '=', $data['custom_fields']],
        ]);

        if (($data['region_id'] ?? null) !== null) {
            $query->where('region_id', '=', $data['region_id']);
        } else {
            $query->whereNull('region_id');
        }

        return $query->first();
    }

    public function create(array $data): PimCustomerAddress
    {
        return PimCustomerAddress::create($data);
    }
}
