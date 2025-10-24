<?php

namespace App\Models\Pim\Property\PropertyGroupOption;

use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimPropertyGroupOption extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_property_group_option';

    protected $fillable = [
        'id',
        'name',
        'position',
        'group_id',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getPropertyGroupOptionId($model->name, $model->group_id);
        });
    }

    public function propertyGroup(): BelongsTo
    {
        return $this->belongsTo(PimPropertyGroup::class, 'group_id', 'id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PimPropertyGroupOptionTranslation::class, 'property_group_option_id', 'id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(PimProduct::class, 'product_properties', 'option_id', 'product_id');
    }
}
