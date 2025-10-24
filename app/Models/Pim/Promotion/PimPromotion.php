<?php

namespace App\Models\Pim\Promotion;

use App\Models\Pim\Order\PimOrderLineItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimPromotion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_promotion';

    protected $fillable = [
        'name',
        'active',
        'valid_form',
        'valid_to',
        'code',
    ];

    public function lineItem(): HasMany
    {
        return $this->hasMany(PimOrderLineItem::class, 'promotion_id', 'id');
    }
}
