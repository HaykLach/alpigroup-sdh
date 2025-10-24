<?php

namespace App\Models\Pim\Order;

use App\Models\Pim\Customer\PimCustomer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimOrderCustomer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_order_customer';

    protected $fillable = [
        'customer_id',
        'order_id',
        'email',
        'first_name',
        'last_name',
        'title',
        'vat_ids',
        'company',
        'custom_fields',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(PimCustomer::class, 'customer_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PimOrder::class, 'order_id', 'id');
    }
}
