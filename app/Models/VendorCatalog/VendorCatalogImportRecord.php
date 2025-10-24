<?php

namespace App\Models\VendorCatalog;

use App\Enums\VendorCatalog\VendorCatalogImportRecordState;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorCatalogImportRecord extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'data' => 'array',
        'state' => VendorCatalogImportRecordState::class,
    ];

    protected $guarded = [];

    public function file(): BelongsTo
    {
        return $this->belongsTo(
            related: VendorCatalogImport::class,
            foreignKey: 'vendor_catalog_import_id'
        );
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(
            related: VendorCatalogEntry::class,
            foreignKey: 'vendor_catalog_entry_id'
        );
    }
}
