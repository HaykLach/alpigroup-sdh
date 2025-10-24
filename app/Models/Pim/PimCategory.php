<?php

namespace App\Models\Pim;

use App\Models\Pim\Product\PimProduct;
use App\Traits\WithParent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PimCategory extends Model
{
    use HasUuids, SoftDeletes, WithParent;

    protected $fillable = [
        'parent_id',
        'name',
        'description',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(PimProduct::class, 'pim_product_categories', 'category_id', 'product_id');
    }
}
