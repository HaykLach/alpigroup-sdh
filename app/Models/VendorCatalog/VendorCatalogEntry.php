<?php

namespace App\Models\VendorCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCatalogEntry extends Model
{
    use HasFactory, HasUuids;

    protected $casts = [
        'data' => 'array',
    ];

    protected $guarded = [];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(
            related: VendorCatalogVendor::class,
            foreignKey: 'vendor_catalog_vendor_id'
        );
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(
            related: VendorCatalogImportRecord::class,
            foreignKey: 'vendor_catalog_import_record_id'
        );
    }
}
