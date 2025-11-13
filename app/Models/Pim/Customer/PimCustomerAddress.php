<?php

namespace App\Models\Pim\Customer;

use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Region\PimRegion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimCustomerAddress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_customer_address';

    protected $fillable = [
        'id',
        'zipcode',
        'country_id',
        'salutation_id',
        'first_name',
        'last_name',
        'city',
        'street',
        'additional_address_line_1',
        'phone_number',
        'region_id',
        'customer_id',
        'vat_id',
        'custom_fields',
    ];

    public function getFormattedAddressAttribute()
    {
        return "{$this->first_name} {$this->last_name} - {$this->street}, {$this->zipcode} {$this->city}";
    }

    public function customers(): BelongsTo
    {
        return $this->belongsTo(PimCustomer::class, 'customer_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(PimCountry::class, 'country_id', 'id');
    }

    public function salutation(): BelongsTo
    {
        return $this->belongsTo(PimCustomerSalutation::class, 'salutation_id', 'id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(PimRegion::class, 'region_id', 'id');
    }
}
