<?php

namespace App\Models\Pim\Customer;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimCustomerGroup extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_customer_group';

    protected $fillable = [
        'name',
        'custom_fields',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(EventProcessableModel::class, 'group_id', 'id');
    }
}
