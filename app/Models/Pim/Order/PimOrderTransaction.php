<?php

namespace App\Models\Pim\Order;

use App\Models\Pim\PaymentMethod\PimPaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimOrderTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_order_transaction';

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'state',
        'amount',
        'custom_fields',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PimOrder::class, 'order_id', 'id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PimPaymentMethod::class, 'payment_method_id', 'id');
    }
}
