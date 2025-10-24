<?php

namespace App\Models\Pim\SalesChannel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PimSalesChannel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    protected $table = 'pim_sales_channels';
}
