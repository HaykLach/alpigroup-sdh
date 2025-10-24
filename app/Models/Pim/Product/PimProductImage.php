<?php

namespace App\Models\Pim\Product;

use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// #[ObservedBy([PimProductObserver::class])]
class PimProductImage extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getProductImageId($model->product_id, $model->url);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PimProduct::class, 'product_id', 'id');
    }
}
