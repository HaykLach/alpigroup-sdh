<?php

namespace App\Models\Pim;

use App\Observers\PimLanguageObserver;
use App\Services\Pim\PimGenerateIdService;
use App\Traits\WithParent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * App\Models\Pim\PimLanguage
 *
 * @property string $id
 * @property string|null $parent_id
 * @property string $name
 * @property string $pim_local_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PimLanguage> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\Pim\PimLocal|null $local
 * @property-read PimLanguage|null $parent
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage query()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage wherePimLocalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|PimLanguage withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy([PimLanguageObserver::class])]
class PimLanguage extends Model
{
    use HasFactory, HasUuids, SoftDeletes, WithParent;

    protected $guarded = [];

    public function local(): BelongsTo
    {
        return $this->belongsTo(PimLocal::class, 'pim_local_id', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = PimGenerateIdService::getLanguageId($model->name);
        });
    }

    public static function getAllWithLocalKeyedByCode(): Collection
    {
        return self::query()->with('local')->get()->keyBy('local.code');
    }
}
