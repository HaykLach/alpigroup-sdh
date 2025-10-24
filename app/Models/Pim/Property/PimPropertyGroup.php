<?php

namespace App\Models\Pim\Property;

use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimPropertyGroup extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_property_group';

    protected $fillable = [
        'id',
        'name',
        'description',
        'filterable',
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
            $model->id = PimGenerateIdService::getPropertyGroupId($model->name);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PimPropertyGroupTranslation::class, 'property_group_id', 'id');
    }

    public function groupOptions(): HasMany
    {
        return $this->hasMany(PimPropertyGroupOption::class, 'group_id', 'id');
    }
}
