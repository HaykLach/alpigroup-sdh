<?php

namespace App\Models\Pim\Customer;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimCustomerBranch extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function translations(): HasMany
    {
        return $this->hasMany(PimCustomerBranchTranslation::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(PimCustomer::class);
    }
}
