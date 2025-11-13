<?php

namespace App\Models\Pim\Region;

use App\Models\Pim\Customer\PimCustomerAddress;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimRegion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_region';

    protected $fillable = [
        'id',
        'external_id',
        'code',
        'display_name',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(PimCustomerAddress::class, 'region_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PimRegionTranslation::class, 'pim_region_id');
    }
}
