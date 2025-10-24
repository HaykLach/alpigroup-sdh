<?php

namespace App\Models\Pim\Property\PropertyGroupOption;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PimPropertyGroupOptionTranslation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pim_property_group_option_translations';

    protected $fillable = [
        'language_id',
        'name',
        'position',
        'property_group_option_id',
    ];

    public $timestamps = false;

    public function propertyGroupOptions(): BelongsTo
    {
        return $this->belongsTo(PimPropertyGroupOption::class, 'property_group_option_id', 'id');
    }
}
