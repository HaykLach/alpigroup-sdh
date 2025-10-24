<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDetailImport extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'customer_detail_import';

    protected $fillable = [
        'data',
        'status',
        'result',
        'customer_import_id'
    ];


    public function customerImport(): BelongsTo
    {
        return $this->belongsTo(CustomerImport::class);
    }
}
