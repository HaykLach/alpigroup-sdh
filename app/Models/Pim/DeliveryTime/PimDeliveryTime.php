<?php

namespace App\Models\Pim\DeliveryTime;

use App\Models\Pim\ShippingMethod\PimShippingMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimDeliveryTime extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_delivery_time';

    protected $fillable = [
        'min',
        'max',
        'unit',
    ];

    public function shippingMethod(): HasMany
    {
        return $this->hasMany(PimShippingMethod::class, 'delivery_time_id', 'id');
    }
}
