<?php

namespace App\Models\Pim\Region;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimRegionTranslation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_region_translation';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'pim_region_id',
        'language_id',
        'name',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(PimRegion::class, 'pim_region_id');
    }
}
