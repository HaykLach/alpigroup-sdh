<?php

namespace App\Models\Pim\Product;

use App\Models\Pim\PimCategory;
use App\Models\Pim\PimQuotation;
use App\Models\Pim\PimTax;
use App\Models\Pim\Property\PropertyGroupOption\PimPropertyGroupOption;
use App\Observers\PimProductObserver;
use App\Services\Pim\PimGenerateIdService;
use App\Traits\WithParent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property Pivot $pivot
 */
#[ObservedBy([PimProductObserver::class])]
class PimProduct extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia, SoftDeletes, WithParent;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'custom_fields' => 'array',
        'prices' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getProductId($model->identifier, $model->product_number);
        });
    }

    public function scopeByProductNumber(Builder $query, string $productNumber): Builder
    {
        return $query->where('product_number', '=', $productNumber);
    }

    public function getSummarizedTitleAttribute(): string
    {
        return "{$this->product_number} - {$this->identifier} - {$this->name}";
    }

    public function getIsMainProductAttribute(): bool
    {
        return $this->parent_id === null;
    }

    public function variations(): HasMany
    {
        return $this->hasMany(PimProduct::class, 'parent_id', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PimProduct::class, 'parent_id', 'id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PimCategory::class, 'pim_product_categories', 'product_id', 'category_id');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(PimPropertyGroupOption::class, 'product_properties', 'product_id', 'option_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PimProductTranslation::class, 'product_id', 'id');
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(PimPropertyGroupOption::class, 'product_options', 'product_id', 'option_id');
    }

    public function quotation(): BelongsToMany
    {
        return $this->belongsToMany(PimQuotation::class, 'quotation_product')
            ->withTimestamps();
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(PimTax::class, 'pim_tax_id', 'id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(PimProductManufacturer::class, 'pim_manufacturer_id', 'id');
    }

    public static function getMediaCollectionPreviewName(): string
    {
        return 'preview';
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $collection = $this->getMediaCollectionPreviewName();

        $this
            ->addMediaConversion('thumbnail')
            ->performOnCollections($collection)
            ->nonQueued()
            ->format('png')
            ->width(80)
            ->height(80);

        $this
            ->addMediaConversion('preview')
            ->performOnCollections($collection)
            ->nonQueued()
            ->keepOriginalImageFormat()
            ->quality(80)
            ->width(320);
    }

    public function quotationProduct(): MorphOne
    {
        return $this->morphOne(PimQuotation::class, 'product');
    }
}
