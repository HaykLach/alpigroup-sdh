<?php

namespace App\Models\Pim;

use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PimCurrency extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_currency';

    protected $fillable = [
        'name',
        'iso_code',
        'short_name',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getCurrencyId($model->iso_code);
        });
    }
}
