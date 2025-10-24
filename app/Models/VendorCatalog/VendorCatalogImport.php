<?php

namespace App\Models\VendorCatalog;

use App\Enums\VendorCatalog\VendorCatalogImportSource;
use App\Enums\VendorCatalog\VendorCatalogImportState;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class VendorCatalogImport extends Model
{
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $casts = [
        'state' => VendorCatalogImportState::class,
        'type' => VendorCatalogImportSource::class,
    ];

    protected $appends = ['human_file_size'];

    protected $guarded = [];

    public function importDefinition(): BelongsTo
    {
        return $this->belongsTo(
            related: VendorCatalogImportDefinition::class,
            foreignKey: 'vendor_catalog_import_definition_id'
        );
    }

    public function records(): HasMany
    {
        return $this->hasMany(
            related: VendorCatalogImportRecord::class,
        );
    }

    public function entries(): HasManyThrough
    {
        return $this->hasManyThrough(
            related: VendorCatalogEntry::class,
            through: VendorCatalogImportRecord::class,
        );
    }

    protected function humanFileSize(): Attribute
    {
        $bytes = $this->attributes['size'] ?? 0;
        $i = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $result = sprintf('%.02F', $bytes / (1024 ** $i)) * 1 .' '.$sizes[$i];

        return new Attribute(
            get: fn () => $result,
        );
    }
}
