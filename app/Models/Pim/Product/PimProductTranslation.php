<?php

namespace App\Models\Pim\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PimProductTranslation extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'pim_product_translations';

    protected $fillable = [
        'language_id',
        'name',
        'description',
        'product_id',
        'custom_fields',
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public $timestamps = true;

    public function products(): BelongsTo
    {
        return $this->belongsTo(PimProduct::class, 'product_id', 'id');
    }
}
