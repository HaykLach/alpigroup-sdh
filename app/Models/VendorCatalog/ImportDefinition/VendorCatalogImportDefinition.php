<?php

namespace App\Models\VendorCatalog\ImportDefinition;

use App\Enums\VendorCatalog\VendorCatalogImportDefinitionProtocolType;
use App\Enums\VendorCatalog\VendorCatalogImportSource;
use App\Models\VendorCatalog\VendorCatalogVendor;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class VendorCatalogImportDefinition extends Model
{
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $casts = [
        'protocol' => VendorCatalogImportDefinitionProtocolType::class,
        'source' => VendorCatalogImportSource::class,
        'configuration' => 'array',
        'file' => 'array',
        'compression' => 'array',
        'setup' => 'array',
        'notification' => 'array',
        'columns' => 'array',
        'mappings' => 'array',
    ];

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getVendorCatalogImportDefinitionId($model->name);
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(
            related: VendorCatalogVendor::class,
            foreignKey: 'vendor_catalog_vendor_id'
        );
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(
            related: VendorCatalogImportDefinitionMapping::class,
        );
    }
}
