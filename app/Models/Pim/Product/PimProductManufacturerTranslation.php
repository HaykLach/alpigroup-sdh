<?php

namespace App\Models\Pim\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PimProductManufacturerTranslation extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'pim_product_manufacturer_translations';

    protected $fillable = [
        'language_id',
        'name',
        'manufacturer_id',
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $timestamps = true;

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(PimProductManufacturer::class, 'manufacturer_id', 'id');
    }
}
