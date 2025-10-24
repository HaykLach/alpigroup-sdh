<?php

namespace App\Models\Pim\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimCustomerBranchTranslation extends Model
{
    protected $guarded = [];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(PimCustomerBranch::class);
    }
}
