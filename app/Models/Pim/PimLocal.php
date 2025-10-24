<?php

namespace App\Models\Pim;

use App\Services\Pim\PimGenerateIdService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Pim\PimLocal
 *
 * @property string $id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal query()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLocal withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PimLocal extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getLocaleId($model->code);
        });
    }
}
