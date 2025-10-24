<?php

namespace App\Models\Pim\Country;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimCountryState extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_country_state';

    protected $fillable = [
        'country_id',
        'name',
        'short_code',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(PimCountry::class, 'country_id', 'id');
    }
}
