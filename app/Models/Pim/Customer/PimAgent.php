<?php

namespace App\Models\Pim\Customer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PimAgent extends PimCustomer
{
    protected static function booted()
    {
        static::addGlobalScope('agents', function (Builder $builder) {
            $builder->agents();
        });
    }

    public function scopeWithCustomersDBCount(Builder $query)
    {
        return $query->addSelect([
            'customers_count_db' => DB::table('pim_customers', 'sub_customers')
                ->selectRaw('count(*)')
                ->whereRaw('JSON_EXTRACT(sub_customers.custom_fields, "$.agent_id") = CAST(pim_customers.id AS CHAR)'),
        ]);
    }

    public function scopeWithQuotationsCount(Builder $query)
    {
        return $query->addSelect([
            'quotations_count' => DB::table('quotation_agent')
                ->selectRaw('count(*)')
                ->leftJoin('pim_quotations', 'quotation_agent.pim_quotation_id', '=', 'pim_quotations.id')
                ->whereNull('pim_quotations.deleted_at')
                ->whereColumn('pim_customer_id', 'pim_customers.id'),
        ]);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(PimCustomer::class, 'custom_fields->agent_id', 'id');
    }

    public function getCustomersCountAttribute(): int
    {
        return $this->customers()->count();
    }
}
