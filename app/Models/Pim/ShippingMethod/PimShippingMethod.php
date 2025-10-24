<?php

namespace App\Models\Pim\ShippingMethod;

use App\Models\Pim\DeliveryTime\PimDeliveryTime;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimShippingMethod extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_shipping_method';

    protected $fillable = [
        'delivery_time_id',
        'name',
        'description',
        'tracking_url',
    ];

    public function deliveryTime(): BelongsTo
    {
        return $this->belongsTo(PimDeliveryTime::class, 'delivery_time_id', 'id');
    }
}
