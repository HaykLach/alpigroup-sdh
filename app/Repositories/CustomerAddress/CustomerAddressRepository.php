<?php

namespace App\Repositories\CustomerAddress;

use App\Contracts\CustomerAddress\CustomerAddressRepositoryInterface;
use App\Models\Pim\Customer\PimCustomerAddress;
use App\Repositories\BaseRepository;

class CustomerAddressRepository extends BaseRepository implements CustomerAddressRepositoryInterface
{
    public function findByData(array $data): ?PimCustomerAddress
    {
        return PimCustomerAddress::where([
            ['customer_id', '=', $data['customer_id']],
            ['zipcode', '=', $data['zipcode']],
            ['country_id', '=', $data['country_id']],
            ['city', '=', $data['city']],
            ['street', '=', $data['street']],
            ['additional_address_line_1', '=', $data['additional_address_line_1'] ?? ''],
            ['phone_number', '=', $data['phone_number'] ?? ''],
            ['region', '=', $data['region'] ?? ''],
            ['first_name', '=', $data['first_name']],
            ['last_name', '=', $data['last_name']],
            ['salutation_id', '=', $data['salutation_id']],
            ['custom_fields', '=', $data['custom_fields']],
        ])->first();
    }

    public function create(array $data): PimCustomerAddress
    {
        return PimCustomerAddress::create($data);
    }
}
