<?php

namespace App\Models\Pim;

use App\Models\Pim\Product\PimProduct;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ------------------------------------------------------
 * SCOPES
 * ------------------------------------------------------
 *
 * @method $this byName(string $name)
 */
class PimTax extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_tax';

    protected $fillable = [
        'id',
        'name',
        'tax_rate',
        'position',
    ];

    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PimProduct::class, 'pim_tax_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getTaxId($model->tax_rate);
        });
    }
}
