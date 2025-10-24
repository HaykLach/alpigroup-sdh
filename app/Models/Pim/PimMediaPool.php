<?php

namespace App\Models\Pim;

use App\Enums\Pim\PimMediaPoolType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PimMediaPool extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia, SoftDeletes;

    protected $fillable = ['name', 'parent_id', 'type', 'description'];

    protected $casts = [
        'type' => PimMediaPoolType::class,
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public static function getMediaCollectionName(): string
    {
        return 'media-pool';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(PimMediaPool::getMediaCollectionName())
            ->useDisk('public');
    }
}
