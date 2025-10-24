<?php

namespace App\Models\Pim\Order;

use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Promotion\PimPromotion;
use App\Traits\WithParent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimOrderLineItem extends Model
{
    use HasFactory, HasUuids, WithParent;

    protected $table = 'pim_order_line_item';

    protected $fillable = [
        'order_id',
        'parent_id',
        'identifier',
        'product_id',
        'promotion_id',
        'label',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'payload',
        'price_definition',
        'type',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PimOrder::class, 'order_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PimProduct::class, 'parent_id', 'id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(PimPromotion::class, 'promotion_id', 'id');
    }
}
