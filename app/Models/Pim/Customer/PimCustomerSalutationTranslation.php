<?php

namespace App\Models\Pim\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimCustomerSalutationTranslation extends Model
{
    protected $fillable = [
        'salutation_id',
        'language_id',
        'letter_name',
        'display_name',
    ];

    public function salutation(): BelongsTo
    {
        return $this->belongsTo(PimCustomerSalutation::class, 'salutation_id');
    }
}
