<?php

namespace App\Models\VendorCatalog;

use App\Enums\Currency;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class VendorCatalogVendor extends Model
{
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $casts = [
        'currency' => Currency::class,
        'contact' => 'array',
    ];

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getVendorCatalogVendorId($model->name);
        });
    }

    public function importDefinitions(): HasMany
    {
        return $this->hasMany(
            related: VendorCatalogImportDefinition::class
        );
    }
}
