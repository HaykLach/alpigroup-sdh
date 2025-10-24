<?php

namespace App\Models\Pim\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimPropertyGroupTranslation extends Model
{
    use HasFactory;

    protected $table = 'pim_property_group_translations';

    protected $fillable = [
        'id',
        'language_id',
        'name',
        'description',
        'property_group_id',
    ];

    public $timestamps = false;

    public function propertyGroup(): BelongsTo
    {
        return $this->belongsTo(PimPropertyGroup::class, 'property_group_id', 'id');
    }
}
