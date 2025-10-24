<?php

namespace App\Models\Pim\Order;

use App\Models\Pim\PimCurrency;
use App\Models\Pim\Tag\PimTag;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_orders';

    protected $fillable = [
        'order_number',
        'currency_id',
        'billing_address_id',
        'price',
        'order_date_time',
        'order_date',
        'amount_total',
        'amount_net',
        'tax_status',
        'shipping_costs',
        'shipping_total',
        'custom_fields',
        'state',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(PimCurrency::class, 'currency_id', 'id');
    }

    public function tag(): BelongsToMany
    {
        return $this->belongsToMany(PimTag::class, 'pim_order_tag', 'order_id', 'tag_id');
    }

    public function orderShippingAddress(): HasMany
    {
        return $this->hasMany(PimOrderAddress::class, 'id', 'billing_address_id');
    }

    public function customer(): HasMany
    {
        return $this->hasMany(PimOrderCustomer::class, 'order_id', 'id');
    }

    public function orderDelivery(): HasMany
    {
        return $this->hasMany(PimOrderDelivery::class, 'order_id', 'id');
    }

    public function lineItem(): HasMany
    {
        return $this->hasMany(PimOrderLineItem::class, 'order_id', 'id');
    }

    public function transaction(): HasMany
    {
        return $this->hasMany(PimOrderTransaction::class, 'order_id', 'id');
    }
}
