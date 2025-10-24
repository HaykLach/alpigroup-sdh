<?php

namespace App\Models\Pim\Tag;

use App\Models\Pim\Order\PimOrder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PimTag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
    ];

    public function order(): BelongsToMany
    {
        return $this->belongsToMany(PimOrder::class, 'pim_irder_tag', 'tag_id', 'order_id');
    }
}
