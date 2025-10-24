<?php

namespace App\Models\Pim\PaymentMethod;

use App\Models\Pim\Customer\PimCustomer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimPaymentMethod extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_payment_method';

    protected $fillable = [
        'name',
        'technical_name',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(PimCustomer::class, 'default_payment', 'id');
    }
}
