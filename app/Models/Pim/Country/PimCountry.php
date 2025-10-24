<?php

namespace App\Models\Pim\Country;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimCountry extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_country';

    protected $fillable = [
        'name',
        'iso',
    ];

    public function countryState(): HasMany
    {
        return $this->hasMany(PimCountryState::class, 'country_id', 'id');
    }
}
