<?php

namespace App\Models\Pim\Product;

use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * ------------------------------------------------------
 * SCOPES
 * ------------------------------------------------------
 *
 * @method $this byName(string $name)
 */
class PimProductManufacturer extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    protected $table = 'pim_product_manufacturers';

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getProductManufacturerId($model->name);
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(PimProduct::class, 'pim_manufacturer_id', 'id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PimProductManufacturerTranslation::class, 'manufacturer_id', 'id');
    }

    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }
}
