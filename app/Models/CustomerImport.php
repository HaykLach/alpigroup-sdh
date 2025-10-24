<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerImport extends Model
{
    use HasFactory;

    protected $table = 'customer_import';

    protected $fillable = [
        'path',
        'file_name',
        'status',
        'result'
    ];

    public function customerDetails()
    {
        return $this->hasMany(CustomerDetailImport::class);
    }
}
