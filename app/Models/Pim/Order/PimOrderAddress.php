<?php

namespace App\Models\Pim\Order;

use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Customer\PimCustomerSalutation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimOrderAddress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_order_address';

    protected $fillable = [
        'order_id',
        'zipcode',
        'country_id',
        'first_name',
        'last_name',
        'salutation_id',
        'city',
        'street',
        'additional_address_line_1',
        'phone_number',
        'region',
        'vat_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PimOrder::class, 'order_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(PimCountry::class, 'country_id', 'id');
    }

    public function salutation(): BelongsTo
    {
        return $this->belongsTo(PimCustomerSalutation::class, 'salutation_id', 'id');
    }
}
