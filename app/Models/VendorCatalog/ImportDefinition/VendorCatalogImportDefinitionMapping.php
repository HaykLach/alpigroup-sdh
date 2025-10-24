<?php

namespace App\Models\VendorCatalog\ImportDefinition;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCatalogImportDefinitionMapping extends Model
{
    use HasFactory;

    public function importDefinition(): BelongsTo
    {
        return $this->belongsTo(
            related: VendorCatalogImportDefinition::class,
            foreignKey: 'vendor_catalog_import_definition_id'
        );
    }
}
