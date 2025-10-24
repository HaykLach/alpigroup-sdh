<?php

namespace App\Models\Pim\Customer;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PimCustomerTaxGroup extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'code',
        'tax_handling',
    ];

    public function customers()
    {
        return $this->hasMany(PimCustomer::class, 'tax_group_id');
    }
}
