<?php

namespace App\Models\Pim\Cache;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PimCacheTranslation extends Model
{
    use HasUuids;

    protected $guarded = [
    ];
}
