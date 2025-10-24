<?php

namespace App\Models\Pim\Order;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimOrderDelivery extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_order_delivery';

    protected $fillable = [
        'order_id',
        'shipping_address_id',
        'tracking_codes',
        'shipping_date_earliest',
        'shipping_date_latest',
        'shipping_costs',
        'custom_fields',
        'shipping_method',
        'state',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PimOrder::class, 'order_id', 'id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(PimOrderAddress::class, 'shipping_address_id', 'id');
    }
}
