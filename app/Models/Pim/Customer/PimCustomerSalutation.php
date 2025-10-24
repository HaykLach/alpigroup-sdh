<?php

namespace App\Models\Pim\Customer;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimCustomerSalutation extends Model
{
    use HasUuids;

    protected $table = 'pim_customer_salutation';

    protected $fillable = [
        'id',
        'salutation_key',
        'letter_name',
        'display_name',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(PimCustomer::class, 'salutation_id', 'id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PimCustomerSalutationTranslation::class, 'salutation_id');
    }
}
