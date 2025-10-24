<?php

namespace App\Models\VendorCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCatalogImportRule extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];
}
